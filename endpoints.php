<?php

$callback_generate_output = function() {
    return 'Lorem Ipsum Dolor Sit Amet ...';
};

cacheable_endpoint('example', 'test-data', $callback_generate_output);
cacheable_endpoint('example', 'test-view', $callback_example_test_view_output);
