
$(function () {

    let fps = 5;
    let worldWidth = 800;
    let worldHeight = 640;

    let cellSize = 32;

    let snakeDirection = {x: 1, y: 0};
    let newDirection = null;

    let gameOver = false;
    let score = 0;
    let $scoreElement = engine.addBarElement('Счет: 0');

    engine.setWorldSize(worldWidth, worldHeight);
    engine.setUpdateCallback(update);
    engine.setKeyDownListener(onKeyDown);
    engine.bindKeyboardListeners();
    engine.setFPS(fps);

    addBoundingWalls(cellSize);

    let $snakeParts = [
        addSnakeHead(256, 320, cellSize, cellSize),
        addSnakeBody(224, 320, cellSize, cellSize),
        addSnakeBody(192, 320, cellSize, cellSize),
    ];

    let $headPart = $snakeParts[0];

    addPoint(0, 0, cellSize, cellSize);
    addPoint(0, 0, cellSize, cellSize);
    addPoint(0, 0, cellSize, cellSize);
    addPoint(0, 0, cellSize, cellSize);

    let $points = engine.getAllElementsOfClass('point');
    let pointObstacleClasses = ['wall', 'point', 'snake-body', 'snake-head'];

    scatterPoints();

    engine.gameLoop();



    function update() {
        if (!isGameOver()) {
            step();

            if (isGameOver()) {
                setOverlayVisible(true);
            }
        }
    }

    function step() {
        updateSnakeDirection();
        checkSnakeCollision();

        if (isGameOver()) {
            return;
        }

        makeSnakeStep();
        checkEatenPoints();
    }

    function updateSnakeDirection() {
        if (newDirection) {
            let isBackwardsDirection = isVectorsEquals(snakeDirection, scaleVector(newDirection, -1));

            if (!isBackwardsDirection) {
                snakeDirection = newDirection;
            }

            newDirection = null;
        }
    }

    function onKeyDown(e) {
        let directionsByKeys = {
            KeyW: {x: 0, y: -1},
            KeyA: {x: -1, y: 0},
            KeyS: {x: 0, y: 1},
            KeyD: {x: 1, y: 0},
        };

        newDirection = newDirection || directionsByKeys[e.code] || null;
    }



    function checkSnakeCollision() {
        let $element = getElementInFrontOfSnake();

        if ($element === null) {
            return;
        }

        if (engine.isElementHasClass($element, 'wall')) {
            console.log($element, getLastSnakePart());
            setGameOver(true);
        }

        if (engine.isElementHasClass($element, 'snake-body')) {
            let $part = getLastSnakePart();
            if (!$element.is($part)) {
                setGameOver(true);
            }
        }
    }

    function getElementInFrontOfSnake() {
        let moveBy = scaleVector(snakeDirection, cellSize);
        let oldPosition = engine.getElementPosition($headPart);
        let newPosition = addVectors(oldPosition, moveBy);
        let obstacleClasses = ['wall', 'snake-body'];
        let $obstacles = engine.getAllElementsOfClasses(obstacleClasses);

        engine.setElementPosition($headPart, newPosition.x, newPosition.y);

        for (const $element of $obstacles) {
            if (engine.isElementsIntersects($element, $headPart)) {
                engine.setElementPosition($headPart, oldPosition.x, oldPosition.y);
                return $element;
            }
        }

        engine.setElementPosition($headPart, oldPosition.x, oldPosition.y);
        return null;
    }

    function checkEatenPoints() {
        for (const $point of $points) {
            if (engine.isElementsIntersects($point, $headPart)) {
                moveElementToRandomFreeCell($point, pointObstacleClasses);
                setScore(score + 1);
                addSnakePart();
            }
        }
    }

    function makeSnakeStep() {
        let moveBy = scaleVector(snakeDirection, cellSize);

        for (let i = $snakeParts.length - 1; i > 0; i--) {
            let $part = $snakeParts[i];
            let $nextPart = $snakeParts[i - 1];
            let nextPosition = engine.getElementPosition($nextPart);

            engine.setElementPosition($part, nextPosition.x, nextPosition.y);
        }

        engine.moveElementBy($headPart, moveBy.x, moveBy.y);
    }

    function getLastSnakePart() {
        return $snakeParts[$snakeParts.length - 1];
    }

    function addSnakePart() {
        let $lastPart = getLastSnakePart();
        let position = engine.getElementPosition($lastPart);
        let size = engine.getElementSize($lastPart);

        let $newPart = addSnakeBody(position.x, position.y, size.width, size.height);

        $snakeParts.push($newPart);
    }

    function scatterPoints() {
        for (const $point of $points) {
            moveElementToRandomFreeCell($point, pointObstacleClasses);
        }
    }

    function moveElementToRandomFreeCell($element, obstacleClasses) {
        let position = findRandomFreeCellForElement($element, obstacleClasses);

        engine.setElementPosition($element, position.x, position.y);
    }

    function findRandomFreeCellForElement($element, obstacleClasses) {
        let position;
        let limit = 1000;

        do {
            position = vectorToCellPosition(engine.getRandomPosition());

            if (limit-- === 0) {
                throw new Error('do-while limit exceeded');
            }
        } while (!engine.isPlaceFreeForElement($element, position.x, position.y, obstacleClasses));

        return position;
    }

    function vectorToCellPosition(vector) {
        return {
            x: Math.floor(vector.x / cellSize) * cellSize,
            y: Math.floor(vector.y / cellSize) * cellSize,
        };
    }

    function addSnakeHead(x, y, w, h) {
        return addElement('snake-head', x, y, w, h);
    }

    function addSnakeBody(x, y, w, h) {
        return addElement('snake-body', x, y, w, h);
    }

    function addPoint(x, y, w, h) {
        return addElement('point', x, y, w, h);
    }

    function addBoundingWalls(size) {
        addWall(0, 0, size, worldHeight);
        addWall(0, 0, worldWidth, size);
        addWall(worldWidth - size, 0, size, worldHeight);
        addWall(0, worldHeight - size, worldWidth, size);
    }

    function addWall(x, y, w, h) {
        return addElement('wall', x, y, w, h);
    }

    function addElement(className, x, y, w, h) {
        let $element = engine.createElement(className, x, y, w, h);

        engine.addElementToBoard($element);

        return $element;
    }

    function setGameOver(value) {
        gameOver = value;
    }

    function isGameOver() {
        return gameOver;
    }

    function setScore(newScore) {
        score = newScore;
        engine.setBarElementText($scoreElement, 'Счет: ' + score);
    }
});