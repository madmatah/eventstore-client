<?php
use EventLoop\EventLoop;
use Rx\Observer\CallbackObserver;
use Rx\Scheduler\EventLoopScheduler;
use Rxnet\EventStore\Data\WriteEventsCompleted;
use Rxnet\EventStore\NewEvent\JsonEvent;

require '../vendor/autoload.php';

$eventStore = new \Rxnet\EventStore\EventStore();
\Rxnet\await($eventStore->connect());

\Rx\Observable::interval(10)
    ->flatMap(
        function ($i) use ($eventStore) {
            $event = new JsonEvent('/truc/chose', ['i' => $i]);
            return $eventStore->write('domain-test-1.fr', [$event]);
        }
    )
    ->subscribe(
        new CallbackObserver(
            function (WriteEventsCompleted $eventsCompleted) {
                gc_collect_cycles();
                $memory = memory_get_usage(true) / 1024 / 1024;
                echo "Last event number {$eventsCompleted->getLastEventNumber()} on commit position {$eventsCompleted->getCommitPosition()} {$memory}Mb \n";
            }
        ),
        new EventLoopScheduler(EventLoop::getLoop())
    );

EventLoop::getLoop()->run();