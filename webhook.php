<?php
    error_reporting(E_ALL);

    /**
     * GitHub webhook handler template.
     * 
     * @see  https://developer.github.com/webhooks/
     * @author  Miloslav Hula (https://github.com/milo)
     * 
     * EDITED / FIXED BY: Jonah Ahvonen (https://github.com/ahvonenj)
     */

    $hookSecret = "secret";  # set NULL to disable check

    set_error_handler(function($severity, $message, $file, $line) 
    {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    set_exception_handler(function($e) 
    {
        header("HTTP/1.1 500 Internal Server Error");
        echo "Error on line {$e->getLine()}: " . htmlSpecialChars($e->getMessage());
        die();
    });

    $rawPost = NULL;
    
    if($hookSecret !== NULL) 
    {
        if(!isset($_SERVER["HTTP_X_HUB_SIGNATURE"])) 
        {
            throw new \Exception("HTTP header 'X-Hub-Signature' is missing.");
        } 
        else if(!extension_loaded("hash")) 
        {
            throw new \Exception("Missing 'hash' extension to check the secret code validity.");
        }

        list($algo, $hash) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
        
        if(!in_array($algo, hash_algos(), TRUE)) 
        {
            throw new \Exception("Hash algorithm '$algo' is not supported.");
        }

        $rawPost = file_get_contents("php://input");
        
        if($hash !== hash_hmac($algo, $rawPost, $hookSecret)) 
        {
            throw new \Exception("Hook secret does not match.");
        }
    };

    if(!isset($_SERVER["CONTENT_TYPE"])) //HTTP_CONTENT_TYPE is not valid (PHP removes these (per CGI/1.1 specification[1]) from the HTTP_ match group.)
    {
        throw new \Exception("Missing HTTP 'Content-Type' header.");
    } 
    else if(!isset($_SERVER["HTTP_X_GITHUB_EVENT"])) 
    {
        throw new \Exception("Missing HTTP 'X-Github-Event' header.");
    }

    switch($_SERVER["CONTENT_TYPE"]) //HTTP_CONTENT_TYPE is not valid (PHP removes these (per CGI/1.1 specification[1]) from the HTTP_ match group.)
    {
        case "application/json":
            $json = $rawPost ?: file_get_contents("php://input");
            break;

        case "application/x-www-form-urlencoded":
            $json = $_POST['payload'];
            break;

        default:
            throw new \Exception("Unsupported content type: $_SERVER[HTTP_CONTENT_TYPE]");
            break;
    }

    # Payload structure depends on triggered event
    # https://developer.github.com/v3/activity/events/types/
    $payload = json_decode($json);

    
    
    switch(strtolower($_SERVER["HTTP_X_GITHUB_EVENT"])) 
    {
        case "ping":
            echo "pong";
            break;

        case "push":
            getRepo($payload);
            break;

    //	case 'create':
    //		break;

        default:
            header("HTTP/1.0 404 Not Found");
            echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
            print_r($payload); # For debug only. Can be found in GitHub hook log.
            die();
            break;
    }
    
    function getRepo($payload)
    {
        if(isset($_GET['projectpath']))
        {
            $fullpath = $_GET['projectpath'] . "/" . $payload->repository->name . "/";
        }
        else
        {
            $fullpath = $payload->repository->name;
        } 
        
        $gitpath = "";
        
        // 1. Check if git is installed
        if(!isGitInstalled())
        {
            echo "Git is not installed" . "\r\n";
            return false;
        }
        else
        {
            $gitpath = exec("which git");
        }
        
        // 2. Resolve if we are cloning some other branch than master
        $branch = "";
    
        if(isset($_GET["branch"]))
        {           
            if(branchExists($payload->repository->clone_url, $_GET["branch"]))
            {
                $branch = "-b " . $_GET["branch"] . " --single-branch ";
            }
            else
            {
                echo "Branch does not exist, cloning without branch handle" . "\r\n";
                $branch = "";
            } 
        }
        else
        {
            $branch = "";
        }      
        
        $gitcommand = "";
        $pull = false;
        
        if(isRepo($fullpath))
        {
            $gitcommand .= $gitpath . " -C " . $fullpath . " pull ";
            $pull = true;
        }
        else
        {
            $gitcommand .= $gitpath . " clone ";
        }
        
        if(!$pull)
            $gitcommand .= $branch;
        
        $gitcommand .= $payload->repository->clone_url;
        
        if(!$pull)
            $gitcommand .= " " . $fullpath;
        
        echo $gitcommand . "\r\n";
        
        
        if($pull)
        {
            echo "Resetting HEAD before pull\r\n";
            
            $rout = null;
            $rval = null;
            exec($gitpath . " -C " . $fullpath . " reset --hard HEAD", $rout, $rval);
            
            print_r($rout);
            echo "\r\n";
            echo "Value: " . $rval . "\r\n";
        }
        
        if(!$pull)
        {
            echo "Mkdir before clone so PHP gets rights to the folder\r\n";
            
            $mout = null;
            $mval = null;
            exec("mkdir -p " . $fullpath . " 2->&1", $mout, $mval);
            
            print_r($mout);
            echo "\r\n";
            echo "Value: " . $mval . "\r\n";
        }
        
        $gout = null;
        $gval = null;
        exec($gitcommand, $gout, $gval);
        
        print_r($gout);
        echo "\r\n";
        echo "Value: " . $gval . "\r\n";

    }
    
    function branchExists($repourl, $branch)
    {
        $branches = array();
            
        exec("git ls-remote --heads " . $repourl, $branches);
        
        $doesbranchexist = false;
        
        foreach($branches as $b) 
        {
            if(strpos($b, $branch) !== false)
            {
                $doesbranchexist = true;
                break;
            }
        }      
        return $doesbranchexist;
    }
    
    function isGitInstalled()
    {
        if(strlen(exec("which git")) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    function isRepo($path)
    {
        $out = null;
        $ec = null;
        exec("git -C " . $path . " status", $out, $ec);

        if($ec === 0)
        {
            return true;
        }
        else
        {
            return false;
        } 
    }
?>