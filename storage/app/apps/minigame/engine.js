$(function () {

    //region Engine Vars

    let $board = $('#board');

    let worldWidth = null;
    let worldHeight = null;
    let display = function () {
    };
    let update = function () {
    };
    let onKeyUp = function () {
    };
    let onKeyDown = function () {
    };
    let onMouseUp = function () {
    };
    let onMouseDown = function () {
    };
    let pressedKeys = {};
    let pressedButtons = {};
    let mouseX = 0;
    let mouseY = 0;

    let fps = 40;
    let frameDuration = 1000 / fps;
    let lag = 0;
    let previous = 0;

    //endregion

    //region Game Lifecycle

    function gameLoop() {
        requestAnimationFrame(gameLoop);

        // calculate the delta or elapsed time since the last frame
        let now = window.performance.now();
        let delta = now - previous;

        // correct any unexpected huge gaps in the delta time
        if (delta > 1000) {
            delta = frameDuration;
        }

        // accumulate the lag counter
        lag += delta;

        // perform an update if the lag counter exceeds or is equal to
        // the frame duration.
        // this means we are updating at a Fixed time-step.
        if (lag >= frameDuration) {

            // update the game logic
            update();

            // reduce the lag counter by the frame duration
            lag -= frameDuration;
        }

        // calculate the lag offset, this tells us how far we are
        // into the next frame
        let lagOffset = lag / frameDuration;

        // display the sprites passing in the lagOffset to interpolate the
        // sprites positions
        display(lagOffset);

        // set the current time to be used as the previous
        // for the next frame
        previous = now;
    }

    function setUpdateCallback(callback) {
        update = callback;
    }

    function setDisplayCallback(callback) {
        display = callback;
    }

    function setFPS(newFps) {
        fps = newFps;
        frameDuration = 1000 / fps;
    }

    //endregion

    //region UI Manipulations

    function addBarElement(initialText) {
        let $element = $(`
            <div class='stats-bar-panel'>
                ${initialText}
            </div>
        `);
        $('#stats-bar').append($element);
        return $element;
    }

    function setBarElementText($element, text) {
        $element.text(text);
    }

    //endregion

    //region Board Manipulations

    function addElementToBoard($element) {
        $board.append($element);
    }

    function createElement(elementClass, x, y, w, h) {
        let $element = $(`<div class='element ${elementClass}'>`);

        setElementPosition($element, x, y);
        setElementSize($element, w, h);

        return $element;
    }

    function setWorldSize(width, height) {
        setElementSize($board, width, height);
        worldWidth = width;
        worldHeight = height;
    }

    function moveElementToRandomFreePlace($element, obstacleClasses) {
        let position = findRandomFreePlaceForElement($element, obstacleClasses);
        setElementPosition($element, position.x, position.y);
    }

    function findRandomFreePlaceForElement($element, obstacleClasses) {
        let position;
        let limit = 1000;

        do {
            position = getRandomPosition();

            if (limit-- === 0) {
                throw new Error('do-while limit exceeded');
            }
        } while (!isPlaceFreeForElement($element, position.x, position.y, obstacleClasses));

        return position;
    }

    function isPlaceFreeForElement($element, x, y, obstacleClasses) {
        let size = getElementSize($element);
        let newElementBounds = createBoundingRect({x, y}, size);
        let $obstacles = getAllElementsOfClasses(obstacleClasses);

        for (let $obstacle of $obstacles) {
            let obstacleBounds = getElementBoundingRect($obstacle);

            if (isRectanglesIntersects(obstacleBounds, newElementBounds)) {
                return false;
            }
        }

        return true;
    }

    function isElementsIntersects($a, $b) {
        let boundsA = getElementBoundingRect($a);
        let boundsB = getElementBoundingRect($b);

        return isRectanglesIntersects(boundsA, boundsB);
    }

    function getAllElementsOfClasses(classes) {
        let $elements = $board.find('.element').filter((index, element) => {
            for (let cls of classes) {
                if ($(element).hasClass(cls)) {
                    return true;
                }
            }
            return false;
        });
        return $elements.map(function () {
            return $(this);
        });
    }

    function getAllElementsOfClass(elementClass) {
        let $elements = $board.find('.' + elementClass);
        return $elements.map(function () {
            return $(this);
        });
    }

    function getRandomPosition() {
        return {
            x: Math.floor(Math.random() * worldWidth),
            y: Math.floor(Math.random() * worldHeight),
        };
    }

    //endregion

    //region Element Manipulations

    function moveElementAvoidingObstacles($element, x, y, obstacleClasses) {
        let pos = getElementPosition($element);
        let steps = Math.max(Math.abs(x), Math.abs(y));
        let xStep = x / steps;
        let yStep = y / steps;

        for (let i = 0; i < steps; i++) {
            if (xStep !== 0 && isPlaceFreeForElement($element, pos.x + xStep, pos.y, obstacleClasses)) {
                pos.x += xStep;
            }

            if (yStep !== 0 && isPlaceFreeForElement($element, pos.x, pos.y + yStep, obstacleClasses)) {
                pos.y += yStep;
            }
        }

        setElementPosition($element, pos.x, pos.y);
    }

    function moveElementBy($element, x, y) {
        let pos = getElementPosition($element);

        setElementPosition($element, pos.x + x, pos.y + y);
    }

    function setElementHasClass($element, className, isSet) {
        $element.toggleClass(className, isSet);
    }

    function isElementHasClass($element, className) {
        return $element.hasClass(className);
    }

    function getElementBoundingRect($element) {
        let position = getElementPosition($element);
        let size = getElementSize($element);

        return createBoundingRect(position, size);
    }

    function setElementSize($element, width, height) {
        $element.css({
            width: width + 'px',
            height: height + 'px',
        });
    }

    function getElementSize($element) {
        return {
            width: $element.outerWidth(),
            height: $element.outerHeight(),
        };
    }

    function setElementPosition($element, x, y) {
        $element.data('position', {x, y});
        $element.css({
            left: x + 'px',
            top: y + 'px',
        });
    }

    function getElementPosition($element) {
        let position = $element.data('position');

        if (!position) {
            position = $element.position();
            position = {
                x: position.left,
                y: position.top,
            };

            $element.data('position', position);
        }

        return position;
    }

    //endregion

    //region Keyboard Utils

    function bindKeyboardListeners() {
        window.onkeyup = function (e) {
            pressedKeys[e.code] = false;
            onKeyUp(e);
        }

        window.onkeydown = function (e) {
            pressedKeys[e.code] = true;
            onKeyDown(e);
        }
    }

    function setKeyUpListener(listener) {
        onKeyUp = listener;
    }

    function setKeyDownListener(listener) {
        onKeyDown = listener;
    }

    function isAnyKeyDown(...codes) {
        for (let code of codes) {
            if (isKeyDown(code)) {
                return true;
            }
        }
        return false;
    }

    function isKeyDown(code) {
        return !!pressedKeys[code];
    }

    //endregion

    //region Mouse Utils

    function bindMouseListeners() {
        window.onmouseup = function (e) {
            pressedButtons[e.button] = false;

            onMouseUp(e);
        };

        window.onmousedown = function (e) {
            pressedButtons[e.button] = true;

            onMouseDown(e);
        };

        window.onmousemove = function (e) {
            let parentOffset = $board.offset();

            mouseX = Math.floor(e.pageX - parentOffset.left);
            mouseY = Math.floor(e.pageY - parentOffset.top);
        };
    }

    function setMouseUpListener(listener) {
        onMouseUp = listener;
    }

    function setMouseDownListener(listener) {
        onMouseDown = listener;
    }

    function isAnyButtonDown(...buttons) {
        for (let button of buttons) {
            if (isButtonDown(button)) {
                return true;
            }
        }
        return false;
    }

    function isButtonDown(button) {
        return !!pressedButtons[button];
    }

    function getMouseX() {
        return mouseX;
    }

    function getMouseY() {
        return mouseY;
    }

    //endregion

    //region Other Utils

    function isRectanglesIntersects(a, b) {
        return a.minX < b.maxX
            && a.maxX > b.minX
            && a.minY < b.maxY
            && a.maxY > b.minY;
    }

    function createBoundingRect(position, size) {
        return {
            x: position.x,
            y: position.y,
            width: size.width,
            height: size.height,
            minX: position.x,
            minY: position.y,
            maxX: position.x + size.width,
            maxY: position.y + size.height,
        };
    }

    //endregion

    window.engine = {
        // Game Lifecycle
        gameLoop,
        setUpdateCallback,
        setDisplayCallback,
        setFPS,

        // UI Manipulations
        addBarElement,
        setBarElementText,

        // Board Manipulations
        addElementToBoard,
        createElement,
        setWorldSize,
        moveElementToRandomFreePlace,
        findRandomFreePlaceForElement,
        isPlaceFreeForElement,
        isElementsIntersects,
        getAllElementsOfClasses,
        getAllElementsOfClass,
        getRandomPosition,

        // Element Manipulations
        moveElementAvoidingObstacles,
        moveElementBy,
        setElementHasClass,
        isElementHasClass,
        getElementBoundingRect,
        setElementSize,
        getElementSize,
        setElementPosition,
        getElementPosition,

        // Keyboard Utils
        bindKeyboardListeners,
        setKeyUpListener,
        setKeyDownListener,
        isAnyKeyDown,
        isKeyDown,

        // Mouse Utils
        bindMouseListeners,
        setMouseUpListener,
        setMouseDownListener,
        isAnyButtonDown,
        isButtonDown,
        getMouseX,
        getMouseY,

        // Other Utils
        isRectanglesIntersects,
        createBoundingRect,
    };
});