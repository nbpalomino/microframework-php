<?php

return [
    ['GET', '/', ['Core42\Controllers\MainController', 'index']],
    ['GET', '/exception', ['Core42\Controllers\MainController', 'exception']],
    ['GET', '/settings', ['Core42\Controllers\MainController', 'settings']],
    ['GET', '/greet/{name}/', ['Core42\Controllers\MainController', 'greet']],
];