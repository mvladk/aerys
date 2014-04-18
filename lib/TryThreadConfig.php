<?php

namespace Aerys;

class TryThreadConfig extends \Thread {
    private $debug;
    private $config;
    private $bind;
    private $error;
    private $bindTo;
    private $options;
    private $hasExecuted;

    public function __construct($debug, $config, $bind) {
        $this->debug = $debug;
        $this->config = $config;
        $this->bind = $bind;
    }

    public function run() {
        register_shutdown_function([$this, 'shutdown']);
        require __DIR__ . '/../src/bootstrap.php';
        list($reactor, $server, $hosts) = (new Bootstrapper)->boot($this->config, $opt = [
            'debug' => $this->debug,
            'bind' => $this->bind,
        ]);
        $this->bindTo = $hosts->getBindableAddresses();
        $this->options = $server->getAllOptions();
        $this->hasExecuted = TRUE;
    }

    public function shutdown() {
        $fatals = [E_ERROR, E_PARSE, E_USER_ERROR, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING];
        $error = error_get_last();
        if ($error && in_array($error['type'], $fatals)) {
            extract($error);
            $this->error = sprintf("%s in %s on line %d", $message, $file, $line);
        }
    }

    public function getBootResultStruct() {
        if ($this->hasExecuted) {
            return [$this->bindTo, $this->options, $this->error];
        } elseif ($this->error) {
            throw new \RuntimeException(
                $this->error
            );
        } else {
            throw new \LogicException(
                "Cannot retrieve boot results: Thread has not executed"
            );
        }
    }
}
