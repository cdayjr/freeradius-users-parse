#!/usr/bin/php
<?php
// this is designed to parse a very specificly formatted users.info file
// so if there's anything strange going on, it's not going to be pretty
// command-line usage: parse-users.php INPUT-FILE OUTPUT-FILE [OUTPUT-JSON-FILE]
// if no arguments are passed, it reads users.info and saves to users.info
if ($argc >= 3)
{
    $users_filename = $argv[1];
    $output_filename = $argv[2];
    if (isset($argv[3]))
        $json_filename = $argv[3];
}
else
{
    $users_filename = 'users.info';
    $output_filename = 'users.info';
}
$users_file = file_get_contents($users_filename);
$lines = explode(PHP_EOL, $users_file);

$parse_users = 0;
$in_user = false;
$prepend = '';
$append = '';

$users = array();
date_default_timezone_set('EST');

foreach($lines as $line)
{
    $line = trim($line);

    // don't start parsing users until we get to Begin of Users
    // and stop when we get to End of Users
    if ($line == '### Begin of Users')
    {
        $parse_users = 1;
        continue;
    }
    elseif ($line == '### End of Users')
    {
        $parse_users = 2;
        continue;
    }
    if ($parse_users == 1)
    {
        if ($in_user == false)
        {
            if (preg_match('/^\S+\s+Cleartext-Password\s+:=\s+"\S*"$/',$line) == 1)
            {
                $data = preg_split('/Cleartext-Password\s+:=\s+"/',$line);
                $in_user = trim($data[0]);
                $users[$in_user] = array();
                $users[$in_user]['password'] = rtrim(trim($data[1]),'"');
            } // if we don't have a user, ignore the line
        }
        else
        {
            if (preg_match('/^#[\w\s:-]*$/',$line) == 1) // we must have a date here
            {
                $date = explode('#', $line);
                $date = $date[1];
                if (strtotime($date) != false)
                    $users[$in_user]['last-modified'] = date('U',strtotime($date));
            }
            elseif (preg_match('/^#==========$/',$line) == 1) // done with user
            {
                $in_user = false;
            }
        }
    }
    elseif ($parse_users == 0)
    {
        $prepend .= $line . PHP_EOL;
    }
    elseif ($parse_users == 2)
    {
        $append .= $line . PHP_EOL;
    }

}

if (isset($json_filename))
    file_put_contents($json_filename,
        json_encode($users, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL, LOCK_EX);

$output = $prepend;
$output .= '### Begin of Users' . PHP_EOL;
foreach ($users as $user => $data) {
    $output .= $user.'  Cleartext-Password := "' . $data['password'] . '"' . PHP_EOL;
    $output .= '        Fall-Through = Yes' . PHP_EOL;
    if (isset($data['last-modified']))
        $output .= '# ' . date('Y-m-d H:i:s T', $data['last-modified']) . PHP_EOL;
    $output .= '#==========' . PHP_EOL;
}
$output .= '### End of Users' . PHP_EOL;
$output .= rtrim($append);

file_put_contents($output_filename, $output, LOCK_EX);

?>
