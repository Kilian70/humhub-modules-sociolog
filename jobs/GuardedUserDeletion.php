<?php

namespace humhub\modules\sociolog\jobs;

use humhub\modules\queue\ActiveJob;
use humhub\modules\queue\interfaces\ExclusiveJobInterface;
use humhub\modules\sociolog\services\SociologUserDeletionService;

/** Execution-only wrapper; existing serialized Core jobs do not need rewriting. */
class GuardedUserDeletion extends ActiveJob implements ExclusiveJobInterface
{
    public $originalJob;

    public function getExclusiveJobId()
    {
        return $this->originalJob->getExclusiveJobId();
    }

    public function run()
    {
        SociologUserDeletionService::runDeletionJob($this->originalJob);
    }
}
