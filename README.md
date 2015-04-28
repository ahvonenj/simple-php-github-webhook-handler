# simple-php-github-webhook-handler

Simple Github webhook handler in PHP.

Original webhook handler was good, but a bit outdated and I did not like the syntax / code formatting.

## Usage

### Secret

Just modify the ```$hookSecret = "secret";``` and remember to set the same secret in the webhook.

### Event

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


