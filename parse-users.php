#!/usr/bin/php
<?php
// this is designed to parse a very specificly formatted users.info file
// so if there's anything strange going on, it's not going to be pretty
// command-line usage: parse-users.php INPUT-FILE OUTPUT-FILE [OUTPUT-JSON-FILE]
if ($argc >= 3)
{
    //command line stuff for testing
    $users_filename = $argv[1];
    $output_filename = $argv[2];
    if (isset($argv[3])) {
        // do json output as well
        $json_filename = $argv[3];
    }
}
else
{
    $users_filename = 'users.info';
    $output_filename = 'users.info';
}
$users_file = file_get_contents($users_filename);
$lines = explode("\n", $users_file);

$parse_users = false;
$in_user = false;

$users = array();
date_default_timezone_set("EST");

foreach($lines as $line => $content)
{
    $content = trim($content);

    // don't start parsing users until we get to Begin of Users
    // and stop when we get to End of Users
    if ($content == "### Begin of Users")
    {
        $parse_users = true;
        continue;
    }
    elseif ($content == "### End of Users")
    {
        $parse_users = false;
        continue;
    }
    if ($parse_users == true)
    {
        if ($in_user == false)
        {
            if (preg_match("/^\w+\s+Cleartext-Password\s+:=\s+\"\w*\"$/",$content) == 1)
            {
                $data = preg_split("/Cleartext-Password\s+:=\s+\"/",$content);
                $in_user = trim($data[0]);
                $users[$in_user] = array();
                $users[$in_user]['password'] = rtrim(trim($data[0]),"\"");
            }

            else continue; // no username value given
        }
        else
        {
            if (preg_match("/^\s*Fall-Through\s+=\s+Yes$/",$content) == 1) continue;
            elseif (preg_match("/^#[\w\s:-]*$/",$content) == 1) // we must have a date here
            {
                $date = explode("#", $content);
                $date = $date[1];
                if (strtotime($date) != false)
                {
                    $users[$in_user]['last-modified'] = date("U",strtotime($date));
                }
            }
            elseif (preg_match("/^#==========$/",$content) == 1) // done with user
            {
                $in_user = false;
            }
            else
            {
                echo "unidentified content: ".$content."\n";
                // not sure what to do???
            }
        }
    }


            

}

if (isset($json_filename))
    file_put_contents($json_filename,
        json_encode($users,JSON_PRETTY_PRINT)."\n\n",LOCK_EX);


$output = "### Begin of Users\n";
foreach ($users as $user => $data) {
    $output .= $user."\tCleartext-Password := \"".$data["password"]."\"\n";
    $output .= "\t\tFall-Through = Yes\n";
    if (isset($data['last-modified']))
        $output.= "# ".date("Y-m-d H:i:s T",$data['last-modified'])."\n";
    $output.= "#==========\n";
}
$output .= "### End of Users\n";

file_put_contents($output_filename,$output,LOCK_EX);

?>
