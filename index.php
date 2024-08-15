<?php
///////////////////////////////////////////////////////////////////
ini_set('memory_limit', '-1');
ini_set('max_input_time', '-1');
ini_set('max_execution_time', '0');
set_time_limit(0);
///////////////////////////////////////////////////////////////////

// see: https://swiftmailer.symfony.com/docs/introduction.html

require_once __DIR__ . '/vendor/autoload.php';

///////////////// send mail /////////////////
if (isset($_POST['submit'])) {

    ini_set('output_buffering', false);
    ini_set('implicit_flush', true);
    ob_implicit_flush(true);
    
    $data = [];
    
    $hosts = array_filter(explode(',', $_POST['host']));
    $usernames = array_filter(explode(',', $_POST['username']));
    $passwords = array_filter(explode(',', $_POST['password']));
    $from_emails = array_filter(explode(',', $_POST['from_email']));
    $ports = array_filter(explode(',', $_POST['port']));
    $encryptions = array_filter(explode(',', $_POST['encryption']));
    
    $useStreamOptions = $_POST['use_stream_options'] ?? false;
    
    array_push($encryptions, null);
    
    $data = [
        'host' => $hosts,
        'username' => $usernames,
        'password' => $passwords,
        'from_email' => $from_emails,
        'port' => $ports,
        'encryption' => $encryptions,   
    ];
   
    $data = array_map(function($v){
        return array_map('trim', $v);
    }, $data);
   
    $combinations = getCombinations($data);

    $total = count($combinations);
    foreach($combinations as $key => $combination) {
        echo 'Trying combination ' . ++$key . ' of ' . $total . '<br>';

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        usleep(600 * 1000);
            
        try {

            // $transport = new Swift_SendmailTransport('/usr/sbin/sendmail -bs');

            $transport = (new Swift_SmtpTransport($combination['host'], $combination['port'], $combination['encryption']))
                          ->setUsername($combination['username'])
                          ->setPassword($combination['password']);
                          
            if ($useStreamOptions) {
                $transport->setStreamOptions(['ssl' => ['allow_self_signed' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);
            }

            $mailer = new Swift_Mailer($transport);

            $message = (new Swift_Message($_POST['subject']))
              ->setFrom([$combination['from_email'] => $_POST['from_name']])
              ->setTo([$_POST['to_email_address']])
              ->setBody($_POST['body']);

            $result = $mailer->send($message);
            
            if ($result == 1) {
                $success = true;
                
                echo '<pre>';
                echo 'Following config worked';
                print_r($combination);
                echo '</pre>';
                exit;
            }
        } catch (\Exception $e) {
            //echo $e->getMessage() . '<hr>';
        }
    }
}

function getCombinations($arrays) {
	$result = [[]];
	
	foreach ($arrays as $property => $property_values) {
		$tmp = [];
		
		foreach ($result as $result_item) {
			foreach ($property_values as $property_value) {
				$tmp[] = array_merge($result_item, [$property => $property_value]);
			}
		}
		
		$result = $tmp;
	}
	
	return $result;
}

function pp(array $data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    exit;
}
?>
<!doctype html>

<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="author" content="Sarfraz">
	<link rel="icon" type="image/png" href="./favicon.ico">

	<title>SMTP Email Testing Tool</title>

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</head>

<body>

	<?php if (isset($success)) { ?>
		<div class="alert alert-success" role="alert">
		  Email Sent Successfully!
		</div>	
	<?php } ?>

	<div class="container mt-2">
	
        <h1 class="h4 text-center bg-warning rounded p-2">SMTP Email Testing Tool</h1>
	
		<form method="post">
		
            <div class="row mb-1">
                <label for="host" class="col-sm-2 col-form-label">Hosts</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="host" id="host" placeholder="Ex: host.com, host2.com, smtp.host.com, mail.host.com, ssl://mail.host.com">
                </div>
            </div>		

            <div class="row mb-1">
                <label for="username" class="col-sm-2 col-form-label">Usernames</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="username" id="username" placeholder="Ex: username1, username2">
                </div>
            </div>
            
            <div class="row mb-1">
                <label for="password" class="col-sm-2 col-form-label">Passwords</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="password" id="password" placeholder="Ex: password1, password2">
                </div>
            </div>
            
            <div class="row mb-1">
                <label for="from_email" class="col-sm-2 col-form-label">From Emails</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="from_email" id="from_email" placeholder="Ex: user@example.com, another@xyz.com">
                </div>
            </div>
                        
            <div class="row mb-1">
                <label for="port" class="col-sm-2 col-form-label">Ports</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="port" id="port" placeholder="Ex: 25, 465, 587" value="587, 465, 25, 2525">
                </div>
            </div>
            
            <div class="row mb-1">
                <label for="encryption" class="col-sm-2 col-form-label">Encryptions</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="encryption" id="encryption" placeholder="Ex: tls, ssl" value="tls, ssl">
                </div>
            </div>
            
            <div class="row mb-1">
                <div class="col-sm-2"></div>
                <div class="col-sm-10">
                    <input class="form-check-input" type="checkbox" id="use_stream_options" name="use_stream_options">
                    <label class="form-check-label" for="use_stream_options">Use Stream Options (allow_self_signed => true, verify_peer => false)</label>
                </div>
            </div>            
			
			<hr>
			
            <div class="row mb-1">
                <label for="from_name" class="col-sm-2 col-form-label">From Name</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="from_name" id="from_name" value="Mail App">
                </div>
            </div>
            
            <div class="row mb-1">
                <label for="subject" class="col-sm-2 col-form-label">Subject</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="subject" id="subject" value="From Mail App">
                </div>
            </div>
			
            <div class="row mb-1">
                <label for="to_email_address" class="col-sm-2 col-form-label">Recepient Email</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="to_email_address" id="to_email_address" value="sarfraz@eteamid.com">
                </div>
            </div>
            
            <div class="row mb-1">
                <label for="body" class="col-sm-2 col-form-label">Email Body</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="body" id="body" value="Email via Email App">
                </div>
            </div>            
						
			<input class="btn btn-success" type="submit" name="submit" placeholder="Send Email">
		</form>
	</div>
	
</body>
</html>