<?php

namespace APP\submission;

if (!class_exists(Submission::class, false)) {
    class Submission
    {
        public const STATUS_QUEUED = 1;
        public const STATUS_PUBLISHED = 3;
        public const STATUS_DECLINED = 4;
        public const STATUS_SCHEDULED = 5;

        private array $data;

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
