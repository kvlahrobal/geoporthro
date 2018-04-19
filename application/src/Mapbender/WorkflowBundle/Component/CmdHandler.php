<?php
/**
 * TODO: License
 */

namespace Mapbender\WorkflowBundle\Component;

/**
 * Description of CmdHandler
 *
 * @author Paul Schmidt
 */
class CmdHandler
{

    public static function generateMinCmd($container, $cmd)
    {
        $dir  = $container->getParameter("kernel.root_dir");
        $cmd_ = "\"" . $dir . "/console\" " . $cmd;
        return $cmd_;
    }

    public static function generateFullCmd($cmd, $logfilename = null)
    {
        $cmdFull = $cmd;
        if ($logfilename !== null) {
            $logfilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $logfilename;
            $cmdFull .= ' > "' . $logfilePath . '"';
        }
        return $cmdFull;
    }

    public static function runProcess($container, $cmd, $wait = false)
    {
        if (self::isWin()) {
//            self::runWinProcess($container, $cmd, $wait);
        } else {
            self::runUnixProcess($container, $cmd, $wait);
        }
    }

//
//    private static function runWinProcess($container, $cmd, $wait)
//    {
//        $cmdFull = 'start /B ' . $cmd;
//        $date = new \DateTime();
//        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " START CMD:" . $cmdFull);
//        if ($wait) {
//            $handle = popen($cmdFull, 'r');
//            if ($handle) {
//                while (($line = fgets($handle)) !== false) {
//                    $date = new \DateTime();
//                    $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " CMD LINE:" . $line);
//                }
//            } else {
//                $date = new \DateTime();
//                $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " CANNOT OPEN:" . $cmdFull);
//            }
//            pclose($handle);
//        } else {
//            // if there's a better solution, let's hear it...
//            pclose(popen($cmdFull, 'r'));
//        }
//        $date = new \DateTime();
//        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " END  CMD:" . $cmdFull);
//    }

    private static function runUnixProcess($container, $cmd, $wait)
    {
        $cmdFull = $cmd;
        if (!$wait) {
            $cmdFull .= " &";
        }
        $date = new \DateTime();
        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " START CMD:" . $cmdFull);
        exec($cmdFull);
        $date = new \DateTime();
        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " END  CMD:" . $cmdFull);
    }

    public static function runProcessFromApp($container, $cmd, $wait = false)
    {
        if (self::isWin()) {
//            self::runWinProcessFromApp($container, $cmd, $wait);
        } else {
            self::runUnixProcessFromApp($container, $cmd, $wait);
        }
    }

//
//    private static function runWinProcessFromApp($container, $cmd, $wait)
//    {
//        $cmdFull = 'start "EBATOOL" /B ' . $cmd;
//        $date = new \DateTime();
//        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " START CMD:" . $cmdFull);
//        if ($wait) {
//            $handle = popen($cmdFull, 'r');
//            if ($handle) {
//                while (($line = fgets($handle)) !== false) {
//                    $date = new \DateTime();
//                    $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " CMD LINE:" . $line);
//                }
//            } else {
//                $date = new \DateTime();
//                $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " CANNOT OPEN:" . $cmdFull);
//            }
//            pclose($handle);
//        } else {
//            // if there's a better solution, let's hear it...
////            pclose(popen($cmdFull, 'r'));
//            $WshShell = new \COM("WScript.Shell");
//            $oExec = $WshShell->Run($cmdFull , 0, true);
//        }
//        $date = new \DateTime();
//        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " END  CMD:" . $cmdFull);
//    }

    private static function runUnixProcessFromApp($container, $cmd, $wait)
    {
        $cmdFull = $cmd;
        if (!$wait) {
            $cmdFull .= " &";
        }
        $date = new \DateTime();
        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " START CMD:" . $cmdFull);
        exec($cmdFull);
        $date = new \DateTime();
        $container->get('logger')->debug($date->format('d.m.Y H:i:s') . " END  CMD:" . $cmdFull);
    }

    public static function isWin()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return true;
        } else {
            return false;
        }
    }

    public static function isUnix()
    {
        return !self::isWin();
    }

    public static function getProcess($cmdDef)
    {
        if (self::isWin()) {
            return self::getWinProcess($cmdDef);
        } else if (self::isUnix()) {
            return self::getUnixProcess($cmdDef);
        } else {
            return null;
        }
    }
//
//    private static function getWinProcess($cmdDef)
//    {
//        $res_int = -1;
//        $res_arr = array();
//        $res     = exec("wmic process get " . implode(',', $cmdDef['columns']), $res_arr, $res_int);
//        $test    = $cmdDef['test'];
//        $teststr = preg_replace('/[^a-z0-9]/', '', strtolower($test));
//        $result  = array();
//        $num     = 0;
//        foreach ($res_arr as $value) {
//            if ($num === 0) {
//                $result[] = $value;
//                $num      = 1;
//            } else {
//                $valLower = preg_replace('/[^a-z0-9]/', '', strtolower($value));
//                if (strpos($valLower, $teststr) !== false) {
//                    $result[] = $value;
//                }
//            }
//        }
//        return $result;
//    }

    private static function getUnixProcess($cmd)
    {
        $res_int = -1;
        $res_arr = array();
        $res     = exec("ps -aux ", $res_arr, $res_int);
        $teststr = $cmd;
        $result  = array();
        $num     = 0;
        $header  = array();
        foreach ($res_arr as $value) {
            if ($num === 0) {
                $header = preg_split("/[\s]+/", $value);
            } else {
                if(strpos($value,'workflow') !== false){
                    $a = 0;
                }
                $pos = strpos(str_replace('"', "", $value), str_replace('"', "", $teststr));
                if ($pos !== false) {
                    $help = preg_split("/[\s]+/", $value);
                    for ($i = 0; $i < count($header); $i++) {
                        if (strtoupper($header[$i]) === "PID") {
                            $result[] = $help[$i];
                        }
                    }
                }
            }
            $num++;
        }
        return $result;
    }

    public static function isRunning($cmdDef)
    {
        $result = self::getProcess($cmdDef);
        if (is_array($result) && count($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function killProcess($container, $pid)
    {
        if (self::isWin()) {
//            return self::killWinProcess($container, $pid);
        } elseif (self::isUnix()) {
            return self::killUnixProcess($container, $pid);
        } else {
            ;
        }
    }
//
//    private static function killWinProcess($container, $pid)
//    {
//        $cmd     = "TASKKILL /F /PID " . $pid;
//        $res_int = -1;
//        $res_arr = array();
//        $res     = exec($cmd, $res_arr, $res_int);
//        return $res_arr;
//    }

    private static function killUnixProcess($container, $pid)
    {
        $res_int = -1;
        $res_arr = array();
        $res     = exec("kill -9 " . $pid, $res_arr, $res_int);
        return $res_arr;
    }
}
