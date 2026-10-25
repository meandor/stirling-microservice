<?php

namespace Stirling\Core;

/**
 * Class to represent the micro-service status
 *
 * A new status can be registered with a name and a function. The overall status is accumulated
 * over all those functions.
 *
 * @package Stirling\Core
 */
class AppStatus
{
    private $statusCollection;

    private static $appStatus;

    /**
     * AppStatus constructor.
     * @param $statusCollection
     */
    private function __construct()
    {
        $this->statusCollection = [];
    }

    public static function instance()
    {
        if (self::$appStatus == null) {
            self::$appStatus = new AppStatus();
        }
        return self::$appStatus;
    }

    public function reset()
    {
        $this->statusCollection = [];
    }

    public function registerStatus($name, $description, $function)
    {
        $status = ["name" => $name, "description" => $description, "function" => $function];
        array_push($this->statusCollection, $status);
    }

    public function buildStatusPage()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->getStatusArray());
    }

    public function getStatusArray()
    {
        $config = Config::instance();
        $status = [
            "application" => [
                "name" => $config->name,
                "version" => $config->version],
            "git" => $config->git,
            "configuration" => new ConfigurationMasker()->mask($config->getConfigMap()),
            "status" => $this->getStatusOverview(),
            "statusDetails" => $this->aggregateStatusDetails()];
        return $status;
    }

    public function aggregateStatusDetails()
    {
        $result = [];
        foreach ($this->statusCollection as $status) {
            $statusCopy = $status;
            $statusCopy["status"] = call_user_func($statusCopy["function"]) ? "ok" : "error";
            unset($statusCopy["function"]);
            $result[] = $statusCopy;
        }
        return $result;
    }

    public function getStatusOverview()
    {
        $result = true;
        foreach ($this->statusCollection as $status) {
            $result &= call_user_func($status["function"]);
        }

        if ($result) {
            return "ok";
        } else {
            return "error";
        }
    }
}
