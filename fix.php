<?php
$c = file_get_contents("phpunit.xml");
$c = preg_replace("/<testsuite name=\"Feature\">/", "<testsuite name=\"E2E\">\n            <directory>tests/E2E</directory>\n        </testsuite>\n        <testsuite name=\"Feature\">", $c);
file_put_contents("phpunit.xml", $c);

