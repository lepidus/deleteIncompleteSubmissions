<?php

if (!defined('STATUS_QUEUED')) {
    define('STATUS_QUEUED', 1);
    define('STATUS_PUBLISHED', 3);
    define('STATUS_DECLINED', 4);
    define('STATUS_SCHEDULED', 5);
}

if (!class_exists('Submission', false)) {
    class Submission
    {
        private $data;

        public function __construct(array $data)
        {
            $this->data = $data;
        }

        public function getData(string $name)
        {
            return $this->data[$name] ?? null;
        }
    }
}
