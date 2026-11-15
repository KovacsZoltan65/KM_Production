<?php

it('reports a synthetic test failure', function () {
    $marker = (string) getenv('CI_DIAGNOSTIC_SENTINEL');
    fwrite(STDOUT, $marker);
    fwrite(STDERR, $marker);
    throw new RuntimeException($marker);
});
