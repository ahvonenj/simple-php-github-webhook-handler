# "simple"-php-github-webhook-handler

"Simple" Github webhook handler in PHP.

Original webhook handler was good, but a bit outdated and I did not like the syntax / code formatting.

## Usage

### Installation

So pulling and git repository with PHP is not too simple, but not too hard either. Here are the steps I did to make it work. Follow these in order (except the git path thing).

**PHP does not have git in its path**

This means that ```exec("git something");``` in your scripts will not work. Instead you have to get the full path with PHP first by using ```$out = null; exec("which git", $out);``` with php and then use the path returned like ```exec($out . " pull");``` for example. Take example from the git-clone branch.

**You need to create the initial project folder by yourself**

Just ```mkdir``` it where you want it.

**Clone the initial repository to the folder you created**

I couldn't find a way to make PHP clone the repo as it does not have rights to do that before you clone it and set the rights. Use ```git clone repourl.git folder-you-created/```

**PHP needs permission to the repository / project folder**

This means that you must execute ```chown -R www-data:www-data folder-you-created/``` command to the project folder and after that ```chmod -R g+s folder-you-created/``` to the same folder.

If you are not sure about the username of apache / PHP, you can check that by running ```echo exec("whoami");``` with PHP.

**PHP's known_hosts file**

You must do the initial pull by yourself with a ```sudo -u www-data git -C folder-you-created/repository/ pull``` command. This adds the remote to the PHP's known_hosts file. Otherwise you will get a host key verification failed error.

**Example list of commands**

* ```mkdir /var/www/html/myproject/```
* ```git clone https://github.com/ahvonenj/simple-php-github-webhook-handler.git /var/www/html/myproject/simple-github-webhook-handler```
* ```chown -R www-data:www-data /var/www/html/myproject/```
* ```chmod -R g+s /var/www/html/myproject/```
* ```sudo -u www-data git -C /var/www/html/myproject/simple-github-webhook-handler/ pull```

After that the webhook is ready and working.

### Secret

Just modify the ```$hookSecret = "secret";``` and remember to set the same secret in the webhook.

### Events

You script your own events in webhook.php.
Determine what happend on, for example, push or create events.

```
switch(strtolower($_SERVER["HTTP_X_GITHUB_EVENT"])) 
{
  case "ping":
    echo "pong";
    break;
  case "push":
    echo "Working";
    break;
  //case 'create':
    //break;
  default:
    header("HTTP/1.0 404 Not Found");
    echo "Event:$_SERVER[HTTP_X_GITHUB_EVENT] Payload:\n";
    print_r($payload); # For debug only. Can be found in GitHub hook log.
    die();
    break;
}
```

### Original webhook handler by

Miloslav Hula (https://github.com/milo)

https://gist.github.com/milo/daed6e958ea534e4eba3


