
$(function () {

    let $player = null;
    let $scoreSpan = engine.addBarElement('Score: 0');

    const worldWidth = 640;
    const worldHeight = 480;
    const playerSpeed = 8;
    let score = 0;

    engine.setDisplayCallback(display);
    engine.setUpdateCallback(update);

    engine.setWorldSize(worldWidth, worldHeight);
    addBoundingWalls(40);

    addWall(300, 300, 40, 140);
    addWall(300, 40, 40, 140);
    addWall(420, 220, 180, 40);
    addWall(40, 220, 180, 40);

    addPoint(0, 0, 20, 20);
    addPoint(20, 0, 20, 20);
    addPoint(40, 0, 20, 20);
    addPoint(60, 0, 20, 20);
    addPoint(80, 0, 20, 20);

    addPlayer(240, 240, 40, 40);

    scatterPoints();

    engine.bindKeyboardListeners();
    engine.bindMouseListeners();
    engine.gameLoop();

    // Game Object Manipulations

    function display(lagOffset) {
        // Not used
    }

    function update() {
        handleUserInput();
        updateEatenPoints();
    }

    function handleUserInput() {
        let isRightPressed = engine.isAnyKeyDown('KeyD', 'ArrowRight');
        let isLeftPressed = engine.isAnyKeyDown('KeyA', 'ArrowLeft');
        let isDownPressed = engine.isAnyKeyDown('KeyS', 'ArrowDown');
        let isUpPressed = engine.isAnyKeyDown('KeyW', 'ArrowUp');

        let xMovement = (isRightPressed - isLeftPressed) * playerSpeed;
        let yMovement = (isDownPressed - isUpPressed) * playerSpeed;

        if (xMovement !== 0 && yMovement !== 0) {
            xMovement = Math.round(xMovement / Math.SQRT2);
            yMovement = Math.round(yMovement / Math.SQRT2);
        }

        movePlayerBy(xMovement, yMovement);
    }

    function updateEatenPoints() {
        for (let $point of getAllPoints()) {
            if (engine.isElementsIntersects($point, $player)) {
                movePointToRandomFreePlace($point);
                setScore(getScore() + 1);
            }
        }
    }

    // UI Manipulations

    function setScore(newScore) {
        score = newScore;
        engine.setBarElementText($scoreSpan, 'Score: ' + score);
    }

    function getScore() {
        return score;
    }

    // Game Object Manipulations

    function movePlayerBy(byX, byY) {
        engine.moveElementAvoidingObstacles($player, byX, byY, ['wall']);
    }

    function scatterPoints() {
        for (let $point of getAllPoints()) {
            movePointToRandomFreePlace($point);
        }
    }

    function movePointToRandomFreePlace($point) {
        engine.moveElementToRandomFreePlace($point, ['wall', 'player', 'point']);
    }

    function addPlayer(x, y, w, h) {
        $player = engine.createElement('player', x, y, w, h);

        engine.addElementToBoard($player);
    }

    function addPoint(x, y, w, h) {
        let $point = engine.createElement('point', x, y, w, h);

        engine.addElementToBoard($point);
    }

    function addBoundingWalls(width) {
        addWall(0, 0, width, worldHeight);
        addWall(worldWidth - width, 0, width, worldHeight);
        addWall(width, 0, worldWidth - width * 2, width);
        addWall(width, worldHeight - width, worldWidth - width * 2, width);
    }

    function addWall(x, y, w, h) {
        let $wall = engine.createElement('wall', x, y, w, h);

        engine.addElementToBoard($wall);
    }

    function getAllPoints() {
        return engine.getAllElementsOfClass('point');
    }

});
