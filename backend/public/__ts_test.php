<?php
try {
    $ts = '2026-03-14 05:16:23.205908+00';
    $d = new DateTimeImmutable($ts);
    echo 'OK:' . $d->format(DATE_ATOM) . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL:' . $e->getMessage() . PHP_EOL;
}
