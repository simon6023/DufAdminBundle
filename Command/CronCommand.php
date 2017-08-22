<?php
namespace Duf\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

use Duf\CoreBundle\Entity\DufCoreCronDate;
use Duf\CoreBundle\Entity\DufCoreCronTaskTrace;

class CronCommand extends ContainerAwareCommand
{
    protected $container;
    protected $em;

    private $logger;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('dufadmin:cron')
            ->setDescription('Launches Cron Jobs if necessary')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container        = $this->getContainer();
        $this->em               = $this->container->get('doctrine.orm.entity_manager');
        $this->logger           = $this->container->get('monolog.logger.dufcorecron');

        $this->logger->info('General Cron Job start');

        // get cron tasks
        $cron_tasks             = $this->getCronTasks();
        $tasks_count            = 0;

        if (count($cron_tasks) > 0) {
            // get days / hours / minutes IDs
            $form_service   = $this->container->get('duf_admin.dufadminform');
            $days           = $form_service->getDays();
            $hours          = $form_service->getHours();
            $minutes        = $form_service->getMinutes();

            // get current time
            $today          = new \DateTime();
            $current_day    = idate('w', $today->format('U'));
            $current_hour   = $today->format('H');
            $current_minute = $today->format('i');

            // get date of last cron iteration
            $cron_last_exec = $this->getGeneralCronLastExecution();

            foreach ($cron_tasks as $cron_task) {
                $process_cron   = false;

                // check if cron task must be executed this day
                $cron_days      = json_decode($cron_task->getDays());

                if (!in_array($current_day, $cron_days))
                    continue;

                // check if cron task must be executed this hour
                $cron_hours     = json_decode($cron_task->getHours());

                if (!in_array($current_hour, $cron_hours))
                    continue;

                // check if cron task must be executed this minute
                $cron_minutes   = json_decode($cron_task->getMinutes());

                if (in_array($current_minute, $cron_minutes)) {
                    $process_cron = true;
                }
                else {
                    foreach ($cron_minutes as $cron_minute) {
                        // construct minute of next launch
                        $next_launch_date   = $today->format('Y') . '-' . $today->format('m') . '-' . $today->format('d') . ' ' . $current_hour . ':' . $cron_minute . ':00';
                        $next_launch        = new \DateTime($next_launch_date);

                        if ($next_launch > $cron_last_exec && $next_launch <= $today) {
                            $process_cron = true;
                        }
                    }
                }

                if ($process_cron) {
                    // create cron task trace
                    $cron_trace         = new DufCoreCronTaskTrace();
                    $cron_start_date    = new \DateTime();
                    $start_microtime    = microtime(true);

                    $cron_trace->setStartedAt($cron_start_date);
                    $cron_trace->setCronTask($cron_task);

                    // increment tasks counter
                    $tasks_count++;

                    // log job start
                    $this->logger->info('Cron task named "' . $cron_task->getName() . '" start');

                    // call command
                    $command_output = $this->executeCommand($cron_task->getCommand());

                    // log output
                    $this->logger->info($command_output, array('task_name' => $cron_task->getName()));

                    // set last execution date
                    $cron_task->setExecutedAt(new \DateTime());

                    // end date
                    $cron_end_date = new \DateTime();
                    $cron_trace->setEndedAt($cron_end_date);

                    // duration
                    $duration = microtime(true) - $start_microtime;
                    $cron_trace->setDuration($duration);

                    $this->em->persist($cron_task);
                    $this->em->persist($cron_trace);
                    $this->em->flush();

                    // log job end
                    $this->logger->info('Cron task named "' . $cron_task->getName() . '" end');

                    // remove older cron traces
                    $this->removeOlderCronTraces($cron_task);
                }
            }
        }

        // log general cron task execution
        $this->logGeneralCronExecution();

        $this->logger->info('General Cron Job end with ' . $tasks_count . ' tasks performed');
    }

    private function getCliApplication()
    {
        $kernel         = $this->container->get('kernel');
        $application    = new Application($kernel);
        $application->setAutoExit(false);

        return $application;
    }

    private function getCliOutput()
    {
        return new BufferedOutput();
    }

    private function getCronTasks()
    {
        return $this->em->getRepository('DufCoreBundle:DufCoreCronTask')->findBy(
            array(
                'enabled'   => true,
            )
        );
    }

    private function logGeneralCronExecution()
    {
        // remove previous DufCoreCronDate
        $execs = $this->em->getRepository('DufCoreBundle:DufCoreCronDate')->findAll();

        foreach ($execs as $previous_exec) {
            $this->em->remove($previous_exec);
        }

        $this->em->flush();

        $exec = new DufCoreCronDate();
        $exec->setExecutedAt(new \DateTime());

        $this->em->persist($exec);
        $this->em->flush();
    }

    private function getGeneralCronLastExecution()
    {
        $execs = $this->em->getRepository('DufCoreBundle:DufCoreCronDate')->findAll();

        foreach ($execs as $exec) {
            return $exec->getExecutedAt();
        }

        // get cron delay
        $cron_delay         = (int)$this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig('cron_delay');

        $cron_last_exec     = new \DateTime();
        $cron_last_exec->sub(new \DateInterval('PT' . $cron_delay . 'M'));

        return $cron_last_exec;
    }

    private function executeCommand($command)
    {
        $kernel         = $this->container->get('kernel');
        $application    = new Application($kernel);
        $output         = new BufferedOutput();
        $input          = new ArrayInput(
                                array(
                                        'command'           => $command,
                                    )
                                );


        $application->setAutoExit(false);
        $application->run($input, $output);

        // get console output
        return $output->fetch();
    }

    private function removeOlderCronTraces($cron_task)
    {
        $cron_traces = $this->em->getRepository('DufCoreBundle:DufCoreCronTaskTrace')->findBy(
            array(
                'cronTask' => $cron_task,
            )
        );

        $limit = new \DateTime();
        $limit->sub(new \DateInterval('P10D'));

        foreach ($cron_traces as $cron_trace) {
            if ($cron_trace->getEndedAt() < $limit) {
                $this->em->remove($cron_trace);
            }
        }

        $this->em->flush();
    }
}