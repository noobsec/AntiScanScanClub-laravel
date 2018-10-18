<?php

namespace noobsec\AntiScanScanClub;

use Illuminate\Foundation;
use Illuminate\Http\Request;
use Storage;

class AntiScanScanClub
{
    /**
     * @var string $filterRules
    */
	private $filterRules = "filter_rules.json";

    /**
     * @var string $filterFiles
    */
    private $filterFiles = "filter_files.txt";

    /**
     * @var string $defaultBlacklists
    */
    private $defaultBlacklists = "blacklists.json";

    /**
     * AntiScanScanClub.
     *
     */
    public function __construct() {
    	$this->list = config('antiscanscanclub.list');
    	$this->options = config('antiscanscanclub.options');
    	$this->abort = ($this->options['return'] == NULL ? 403 : $this->options['return']);

    	$getBlacklists = $this->getBlacklists();
		$this->list_object = json_decode($getBlacklists, TRUE);
		if ($this->list_object === NULL) $this->purgeBlacklistsFile();
    }

    /**
	 * Get blacklists data
	 *
	 * @return string
	 *
	 * @throws \Exception
	*/
    private function getBlacklists() {
    	try {
            $get = Storage::get($this->list);
            return $get;
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            $this->purgeBlacklistsFile();
        } catch (\Exception $e) {
            throw new \Exception("Error while get blacklists File: " . $e->getMessage(), 1);
        }
    }

    /**
     * Search client IP in blacklists file
     *
     * @param string $clientIp the visitor client IP
     * @return bool/integer
    */
    private function searchIp($clientIp) {
    	try {
    		if (($key = array_search($clientIp, array_column($this->list_object, "client_ip"), TRUE)) !== FALSE) {
		    	return $key;
		    } else {
		    	return FALSE;
		    }
    	} catch(\Exception $e) {
    		return FALSE;
    	}
    }

    /**
     * Check whether the client IP has been blocked or not
     *
     * @param string $clientIp the visitor client IP
     * @return void/bool
    */
    public function checkIp($clientIp) {
    	if ($this->searchIp($clientIp) !== FALSE) {
			return abort($this->abort);
    	} else {
			return FALSE;
    	}
    }

    /**
     * Prevention of illegal input based on filter rules file
     *
     * @param array $data the request data
     * @param bool $blocker add client IP to blacklists if contains illegal input
     * @param $clientIp the visitor client IP
     * @return void/bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
    */
    public function filterInput($data = [], $blocker = FALSE, $clientIp) {
    	$filterRules = __DIR__ . "/" . $this->filterRules;
    	$getRule = @file_get_contents($filterRules);

    	if ($getRule === FALSE) {
    		throw new \Exception("Error Processing filter rules File!", TRUE);	
    	}

    	$objectRules = json_decode($getRule, TRUE)['filters'];

    	foreach ($data as $key => $value) {
	    	foreach ($objectRules as $key => $object) {
                if (is_array($value)) {
                    foreach ($value as $key => $array) {
                        $filtered = preg_match("/" . $object['rule'] . "/", $array);
                        $value = $array;
                        if ($filtered) break;
                    }
                } else {
                    $filtered = preg_match("/" . $object['rule'] . "/", $value);
                }

	    		if ($filtered) {
	    			if ($blocker === TRUE) $this->addToBlacklisted($clientIp, $object['description'] . " (" . $value . ")");
	    			return abort($this->abort);
	    		}
	    	}
    	}

    	return FALSE;
    }


    /**
     * Prevention of access to credentials and/ important files/path
     * e.g: wp-admin.php, .git/, backups.tar.gz, www.sql (see many more at filter_files.txt)
     *
     * @param array $data the request data
     * @param bool $blocker add client IP to blacklists if trying to credentials and/ important files/path
     * @param $clientIp the visitor client IP
     * @return void/bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
    */
    public function filterFile($url = NULL, $blocker = FALSE, $clientIp) {
        $filterFiles = __DIR__ . "/" . $this->filterFiles;
        $getFile = @file_get_contents($filterFiles);

        if ($getFile === FALSE) {
            throw new \Exception("Error Processing filter Files File!", TRUE);  
        }

        $objectFiles = file($filterFiles);

        foreach ($objectFiles as $key => $value) {
            $file = trim($value);
            if (substr($url, 1) === trim($file)) {
                if ($blocker === TRUE) $this->addToBlacklisted($clientIp, "Trying to access " . $file);
                return abort($this->abort);
            }
        }

        return FALSE;
    }

    /**
     * Add client IP to blacklists rule
     *
     * @param string $clientIp the visitor client IP
     * @param string $attack is attack type
     * @return bool
    */
    public function addToBlacklisted($clientIp, $attack = NULL) {
    	$add = $this->list_object;
    	$data = [
    		'client_ip' => $clientIp,
    		'attack_type' => ($attack = NULL ? "added manually" : $attack),
    		'date_time' => date('Y-m-d H:i:s')
    	];
    	array_push($add, $data);

    	return $this->writeToBlacklistsFile($add);
    }


	/**
     * Clean the client IP from blacklists
     *
     * @return array
    */
	public function cronBlacklistedRules() {
		foreach ($this->list_object as $key => $object) {
			$getDiffInHours = (int) round(abs(strtotime('now') - strtotime($object['time'])) / 3600, 0);
			if ($getDiffInHours >= $this->options['expired']) {
				return $this->removeFromBlacklists($object['client_ip']);
			}
		}
	}


    /**
     * Remove client IP from blacklists rule
     *
     * @param string $clientIp the visitor client IP
     * @return callable
    */
    public function removeFromBlacklists($clientIp) {
    	$searchIp = $this->searchIp($clientIp);
		if ($searchIp !== FALSE) {
	    	unset($this->list_object[$searchIp]);
	    }
	    return $this->writeToBlacklistsFile($this->list_object);
	}


	/**
     * Purge and/ clean all client IPs from blacklists
     *
     * @return callable
    */
    public function purgeBlacklistsFile() {
    	return $this->writeToBlacklistsFile([]);
    }


    /**
     * Write visitor data to blacklists file
     *
     * @param array $data the visitor data (such as client IP, attack type, etc)
     * @return bool
     *
     * @throws \Exception
    */
    private function writeToBlacklistsFile($data = []) {
    	$write = Storage::put(($this->list == NULL ? $this->defaultBlacklists : $this->list), json_encode($data, JSON_PRETTY_PRINT));

    	if ($write === FALSE) {
    		throw new \Exception("Error While writing to blacklists File!", TRUE);
    	} else {
    		return TRUE;
    	}
    }
}