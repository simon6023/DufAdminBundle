<?php

namespace Duf\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class CronController extends Controller
{
    public function logsAction()
    {
        $logs       = null;
        $logs_dir   = $this->get('kernel')->getLogDir();

        if (!is_dir($logs_dir))
            $this->addFlash('error', 'Logs directory does not exist.');

        // get files
        if (is_dir($logs_dir))
            $logs = $this->getLogs($logs_dir);

        return $this->render('DufAdminBundle:Cron:logs.html.twig', array('logs' => $logs));
    }

    public function deleteAction($timestamp)
    {
        $logs = $this->getLogs();

        foreach ($logs as $log) {
            if ($log['timestamp'] !== $timestamp)
                continue;

            unlink($log['filename']);

            $this->addFlash('success', 'Log ' . $log['filename'] . ' has been deleted.');
        }

        return $this->redirect($this->generateUrl('duf_admin_cron_logs'));
    }

    public function viewAction($timestamp)
    {
        $logs = $this->getLogs();

        foreach ($logs as $log) {
            if ($log['timestamp'] !== $timestamp)
                continue;

            $log_content = file_get_contents($log['filename']);

            echo '<pre>'; print_r($log_content); echo '</pre>'; exit();
        }
    }

    private function getLogs($logs_dir = null)
    {
        if (null === $logs_dir)
            $logs_dir   = $this->get('kernel')->getLogDir();

        $files  = array();
        $_files = glob($logs_dir . '/*.log');

        if (!is_array($_files))
            return null;

        foreach ($_files as $filename) {
            // only get dufcorecron logs
            if (strpos($filename, 'dufcorecron') === false)
                continue;

            // get file date
            $date_str = explode('.log', $filename);
            $date_str = explode('dufcorecron-', $date_str[0]);

            if (!isset($date_str[1]))
                continue;

            if (strlen($date_str[1]) !== 10)
                continue;

            $log_date = new \DateTime($date_str[1]);

            // get file size
            $filesize = filesize($filename);

            // get log environment
            $env = (strpos($filename, 'dev.dufcorecron') !== false) ? 'dev': 'prod';

            $files[$log_date->format('U')] = array(
                'filename'  => $filename,
                'size'      => $this->formatBytes($filesize, 2),
                'date'      => $log_date,
                'timestamp' => $log_date->format('U'),
                'env'       => $env,
            );
        }

        krsort($files);

        return $files;
    }

    private function formatBytes($bytes, $precision = 2) { 
        $units  = array('octets', 'Ko', 'Mo', 'Go', 'To'); 
        $bytes  = max($bytes, 0); 
        $pow    = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow    = min($pow, count($units) - 1); 

        // Uncomment one of the following alternatives
        $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow]; 
    } 
}
