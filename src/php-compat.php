<?php

if (PHP_VERSION_ID >= 80000) {
    error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}
