<?php

/**
 * Cisco for PHP
 *
 * A PHP class to connect to Cisco IOS devices over Telnet.
 *
 * Copyright (C) 2009 Ray Patrick Soucy
 *
 * LICENSE:
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Cisco
 * @author    Ray Soucy <rps@soucy.org>
 * @version   1.0.7
 * @copyright 2009 Ray Patrick Soucy 
 * @link      http://www.soucy.org/
 * @license   GNU General Public License version 3 or later
 * @since     File available since Release 1.0.5
 */

/**
 * Cisco
 * @package    Cisco
 * @version    Release: @package_version@
 * @deprecated Class deprecated in Release 2.0.0
 */
class Cisco 
{

    private $_hostname;
    private $_password;
    private $_username;
    private $_connection;
    private $_data;
    private $_timeout;
    private $_prompt;
    private $_login_string;

    /**
     * Class Constructor
     * @param  string  $hostname Hostname or IP address of the device
     * @param  string  $password Password used to connect
     * @param  string  $username (Optional) Username used to connect
     * @param  integer $timeout  Connetion timeout (seconds)
     * @return object  Cisco object
     */
    public function __construct($hostname, $password, $username = "", $timeout = 10, $login_string = 'Login: ') 
    {
        $this->_hostname = $hostname;
        $this->_password = $password;
        $this->_username = $username;
        $this->_timeout = $timeout;
        $this->_login_string = $login_string;
    } // __construct

    /**
     * Establish a connection to the device
     */
    public function connect() 
    {
        $this->_connection = fsockopen($this->_hostname, 23, $errno, $errstr, $this->_timeout);
        if ($this->_connection === false) {
            die("Error: Connection Failed for $this->_hostname\n");
        } // if
        stream_set_timeout($this->_connection, $this->_timeout);
        $this->_readTo(': ');
        if (substr($this->_data, -24) == 'Please enter your call: ') {
            $this->_send($this->_username);
            $this->_readTo('-');
        } // if
        $this->_send($this->_password);
        $this->_prompt = '>';
        $this->_readTo($this->_prompt);
        if (strpos($this->_data, $this->_prompt) === false) {
            fclose($this->_connection);
            die("Error: Authentication Failed for $this->_hostname\n");
        } // if
    } // connect

    /**
     * Close an active connection
     */
    public function close() 
    {
        $this->_send('quit');
        fclose($this->_connection);
    } // close

    /**
     * Issue a command to the device
     */
    private function _send($command) 
    {
        fputs($this->_connection, $command . "\r\n");
    } // _send

    /**
     * Read from socket until $char
     * @param string $char Single character (only the first character of the string is read)
     */
    private function _readTo($char) 
    {
        // Reset $_data
        $this->_data = "";
        while (($c = fgetc($this->_connection)) !== false) {
            $this->_data .= $c;
            if ($c == $char[0]) break;
            if ($c == '-') {
                // Continue at --More-- prompt
                if (substr($this->_data, -8) == '--More--') fputs($this->_connection, ' ');
            } // if
        } // while
        // Remove --More-- and backspace
        $this->_data = str_replace('--More--', "", $this->_data);
        $this->_data = str_replace(chr(8), "", $this->_data);
        // Set $_data as false if previous command failed.
        if (strpos($this->_data, '% Invalid input detected') !== false) $this->_data = false;
    } // _readTo

    /**
     * Enable (enter privileged user mode)
     * @param  string  $password Enable password
     * @return boolean True on success  
     */
    public function enable($password) 
    {
        $result = false;
        if ($this->_prompt != '#') {
            $this->_send('enable');
            $this->_readTo(':');
            $this->_send($password);
            if ($this->_data !== false) {
                $this->_prompt = '#';
                $result = true;
            } // if
            $this->_readTo($this->_prompt);
            return $result;
        } // if
    } // enable

    /**
     * Disable (exit privileged user mode if enabled)
     */
    public function disable() 
    {
        if ($this->_prompt == '#') {
            $this->_send('disable');
            $this->_prompt = '>';
            $this->_readTo($this->_prompt);
        } // if
    } // disable

    /**
     * Show Logging (execute an IOS "show log" command)
     *
     * Result Array:
     *
     * Array
     * (
     *     [n] => Array
     *         (
     *             [timestamp] => string
     *             [type]      => string
     *             [message]   => string
     *         )
     * )
     *
     * [..]
     * @return mixed|boolean On success returns an array of associative arrays, false on failure.
     */
    public function showLogging() 
    {
        $this->_send('show logging | include %');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        array_shift($this->_data);
        array_pop($this->_data);
        foreach ($this->_data as $entry) {
            $temp = trim($entry);
            $entry = array();
            $entry['timestamp'] = substr($temp, 0, strpos($temp, '%') - 2);
            if ($entry['timestamp'][0] == '.' || $entry['timestamp'][0] == '*') 
                $entry['timestamp'] = substr($entry['timestamp'], 1);
            $temp = substr($temp, strpos($temp, '%') + 1);
            $entry['type'] = substr($temp, 0,  strpos($temp, ':'));
            $temp = substr($temp, strpos($temp, ':') + 2);
            $entry['message'] = $temp;
            array_push($result, $entry);
        } // foreach
        $this->_data = $result;
        return $this->_data;
    } // showLogging

    /**
     * Show Interfaces Status (execute an IOS "show int status" command)
     *
     * Result Array:
     *
     * Array
     * (
     *     [n] => Array
     *         (
     *             [interface]   => string
     *             [description] => string
     *             [status]      => string
     *             [vlan]        => string
     *             [duplex]      => string
     *             [speed]       => string
     *             [type]        => string
     *         )
     * )
     *
     * [..]
     * @return mixed|boolean On success returns an array of associative arrays, false on failure.
     */
    public function showInterfacesStatus() 
    {
        $this->_send('show interfaces status');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        for ($i = 0; $i < 2; $i++) array_shift($this->_data);
        array_pop($this->_data);
        $pos = strpos($this->_data[0], "Status");
        foreach ($this->_data as $entry) {
            $temp = trim($entry);
            if (strlen($temp) > 1 && $temp[2] != 'r' && $temp[0] != '-') {
                $entry = array();
                $entry['interface'] =  substr($temp, 0, strpos($temp, ' '));
                $entry['description'] = trim(substr($temp, strpos($temp, ' ') + 1, 
                    $pos - strlen($entry['interface']) - 1));
                $temp = substr($temp, $pos);
                $temp = sscanf($temp, "%s %s %s %s %s %s");
                $entry['status'] = $temp[0];
                $entry['vlan'] = $temp[1];
                $entry['duplex'] = $temp[2];
                $entry['speed'] = $temp[3];
                $entry['type'] = trim($temp[4] . ' ' . $temp[5]);
                array_push($result, $entry);
            } // if    
        } // foreach
        $this->_data = $result;
        return $this->_data;
    } // showInterfacesStatus;

    /**
     * Show Interface (execute an IOS "show int $int" command)
     *
     * Result Array:
     *
     * Array
     * (
     *     [n] => Array
     *         (
     *             [interface]       => string
     *             [status]          => string
     *             [description]     => string
     *             [mtu]             => string
     *             [bandwidth]       => string
     *             [dly]             => string
     *             [duplex]          => string
     *             [speed]           => string
     *             [type]            => string
     *             [in_rate]         => string
     *             [in_packet_rate]  => string
     *             [out_rate]        => string
     *             [out_packet_rate] => string
     *             [in_packet]       => string
     *             [in]              => string
     *             [broadcast]       => string
     *             [runt]            => string
     *             [giant]           => string
     *             [throttle]        => string
     *             [in_error]        => string
     *             [crc]             => string
     *             [frame]           => string
     *             [overrun]         => string
     *             [ignored]         => string
     *             [watchdog]        => string
     *             [multicast]       => string
     *             [pause_in]        => string
     *             [in_dribble]      => string
     *             [out_packet]      => string
     *             [out]             => string
     *             [underrun]        => string
     *             [out_error]       => string
     *             [collision]       => string
     *             [reset]           => string
     *             [babble]          => string
     *             [late_collision]  => string
     *             [deferred]        => string
     *             [lost_carrier]    => string
     *             [no_carrier]      => string
     *             [pause_out]       => string
     *             [out_buffer_fail] => string
     *             [out_buffer_swap] => string
     *         )
     * )
     *
     * [..]
     * @param  string        $int The interface to query
     * @return array|boolean On success returns an associative array, false on failure.
     */
    public function showInterface($int) 
    {
        $this->_send("show interface $int");
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        foreach ($this->_data as $entry) {
            $entry = trim($entry);
            if (strpos($entry, 'line protocol') !== false) {
                $result['interface'] = substr($entry, 0, strpos($entry, ' '));
                if (strpos($entry, 'administratively') !== false) {
                    $result['status'] = 'disabled';
                } elseif (substr($entry, strpos($entry, 'line protocol') + 17, 2) == 'up') {
                    $result['status'] = 'connected';
                } else {
                    $result['status'] = 'notconnect';
                } // if .. else
            } elseif (strpos($entry, 'Description: ') !== false) {
                $entry = explode(':', $entry);
                $result['description'] = trim($entry[1]);
            } elseif (strpos($entry, 'MTU') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['mtu'] = $entry[0][1];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['bandwidth'] = $entry[1][1];
                $entry[2] = trim($entry[2]);
                $entry[2] = explode(' ', $entry[2]);
                $result['dly'] = $entry[2][1];
            } elseif (strpos($entry, 'duplex') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $entry[0][0] = explode('-', $entry[0][0]);
                $result['duplex'] = strtolower($entry[0][0][0]);
                $entry[1] = trim($entry[1]);
                if (strpos($entry[1], 'Auto') !== false) {
                    $result['speed'] = 'auto';
                } else {
                    $result['speed'] = intval($entry[1]);
                } // if .. else
                $entry[2] = rtrim($entry[2]);
                $result['type'] = substr($entry[2], strrpos($entry[2], ' ') + 1);
            } elseif (strpos($entry, 'input rate') !== false) {
                $entry = explode(',', $entry);
                $result['in_rate'] = substr($entry[0], strpos($entry[0], 'rate') + 5, 
                    strrpos($entry[0], ' ') - (strpos($entry[0], 'rate') + 5));
                $entry = trim($entry[1]);
                $entry = explode(' ', $entry);
                $result['in_packet_rate'] = $entry[0];
            } elseif (strpos($entry, 'output rate') !== false) {
                $entry = explode(',', $entry);
                $result['out_rate'] = substr($entry[0], strpos($entry[0], 'rate') + 5, 
                    strrpos($entry[0], ' ') - (strpos($entry[0], 'rate') + 5));
                $entry = trim($entry[1]);
                $entry = explode(' ', $entry);
                $result['out_packet_rate'] = $entry[0];
            } elseif (strpos($entry, 'packets input') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['in_packet'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['in'] = $entry[1][0];
                if (count($entry) > 2) {
                    $entry[2] = trim($entry[2]);
                    $entry[2] = explode(' ', $entry[2]);
                    $result['no_buffer'] = $entry[2][0];
                } // if
            } elseif (strpos($entry, 'Received') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['broadcast'] = $entry[0][1];
                if (count($entry) > 1) {
                    $entry[1] = trim($entry[1]);
                    $entry[1] = explode(' ', $entry[1]);
                    $result['runt'] = $entry[1][0];
                    $entry[2] = trim($entry[2]);
                    $entry[2] = explode(' ', $entry[2]);
                    $result['giant'] = $entry[2][0];
                    $entry[3] = trim($entry[3]);
                    $entry[3] = explode(' ', $entry[3]);
                    $result['throttle'] = $entry[3][0];
                } // if
            } elseif (strpos($entry, 'CRC') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['in_error'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['crc'] = $entry[1][0];
                $entry[2] = trim($entry[2]);
                $entry[2] = explode(' ', $entry[2]);
                $result['frame'] = $entry[2][0];
                $entry[3] = trim($entry[3]);
                $entry[3] = explode(' ', $entry[3]);
                $result['overrun'] = $entry[3][0];
                $entry[4] = trim($entry[4]);
                $entry[4] = explode(' ', $entry[4]);
                $result['ignored'] = $entry[4][0];
            } elseif (strpos($entry, 'watchdog') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['watchdog'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['multicast'] = $entry[1][0];
                if (count($entry) > 2) {
                    $entry[2] = trim($entry[2]);
                    $entry[2] = explode(' ', $entry[2]);
                    $result['pause_in'] = $entry[2][0];
                } // if
            } elseif (strpos($entry, 'dribble') !== false) {
                $entry = trim($entry);
                $entry = explode(' ', $entry);
                $result['in_dribble'] = $entry[0];
            } elseif (strpos($entry, 'packets output') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['out_packet'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['out'] = $entry[1][0];
                $entry[2] = trim($entry[2]);
                $entry[2] = explode(' ', $entry[2]);
                $result['underrun'] = $entry[2][0];
            } elseif (strpos($entry, 'output errors') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['out_error'] = $entry[0][0];
                if (count($entry) > 2) {
                    $entry[1] = trim($entry[1]);
                    $entry[1] = explode(' ', $entry[1]);
                    $result['collision'] = $entry[1][0];
                    $entry[2] = trim($entry[2]);
                    $entry[2] = explode(' ', $entry[2]);
                    $result['reset'] = $entry[2][0];
                } else {
                    $entry[1] = trim($entry[1]);
                    $entry[1] = explode(' ', $entry[1]);
                    $result['reset'] = $entry[1][0];
                } // if .. else
            } elseif (strpos($entry, 'babbles') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['babble'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['late_collision'] = $entry[1][0];
                $entry[2] = trim($entry[2]);
                $entry[2] = explode(' ', $entry[2]);
                $result['deferred'] = $entry[2][0];
            } elseif (strpos($entry, 'lost carrier') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['lost_carrier'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['no_carrier'] = $entry[1][0];
                if (count($entry) > 2) {
                    $entry[2] = trim($entry[2]);
                    $entry[2] = explode(' ', $entry[2]);
                    $result['pause_out'] = $entry[2][0];
                } // if
            } elseif (strpos($entry, 'output buffer failures') !== false) {
                $entry = explode(',', $entry);
                $entry[0] = trim($entry[0]);
                $entry[0] = explode(' ', $entry[0]);
                $result['out_buffer_fail'] = $entry[0][0];
                $entry[1] = trim($entry[1]);
                $entry[1] = explode(' ', $entry[1]);
                $result['out_buffer_swap'] = $entry[1][0];
            } // if .. elseif
        } // foreach
        $this->_data = $result;
        return $this->_data;
    } // showInterface

    /**
     * Show ARP (execute an IOS "show arp" command)
     *
     * Result Array:
     *
     * Array
     * (
     *     [n] => Array
     *         (
     *             [ip]          => string
     *             [mac_address] => string
     *             [age]         => string
     *         )
     * )
     *
     * [..]
     * @return mixed|boolean On success returns an array of associative arrays, false on failure.
     */
    public function showArp() 
    {
        $this->_send('show arp | exclude Incomplete');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        for ($i = 0; $i < 2; $i++) array_shift($this->_data);
        array_pop($this->_data);
        foreach ($this->_data as $entry) {
            $temp = sscanf($entry, "%s %s %s %s %s %s");
            $entry = array();
            $entry['ip'] = $temp[1];
            $entry['mac_address'] = $temp[3];
            if ($temp[2] == '-') $temp[2] = '0';
            $entry['age'] = $temp[2];
            if ($entry['ip'] != 'Address' && $entry['mac_address'] != 'Incomplete') {
                array_push($result, $entry);
            } // if
        } // foreach
        $this->_data = $result;
        return $this->_data;
    } // showArp

    /**
     * Show MAC address-table (execute an IOS "show mac address-table" command)
     *
     * This command is modified to ignore trunk ports.  This keeps the reported address table 
     * isolated to the local switch, provided all uplinks are configured as trunk ports.
     *
     * Support for XL-series switches is included (e.g. 3500-XL)
     *
     * Result Array:
     *
     * Array
     * (
     *     [n] => Array
     *         (
     *             [mac_address] => string
     *             [interface]   => string
     *         )
     * )
     *
     * [..]
     * @return mixed|boolean On success returns an array of associative arrays, false on failure.
     */
    public function showMacAddressTable() 
    {
        $omit = $this->trunkInterfaces();
        $this->_send('show mac address-table | exclude CPU');
        $this->_readTo($this->_prompt);
        $result = array();
        if ($this->_data !== false) {
            $this->_data = str_replace("          ", "", $this->_data);
            $this->_data = explode("\r\n", $this->_data);
            for ($i = 0; $i < 6; $i++) array_shift($this->_data);
            for ($i = 0; $i < 2; $i++) array_pop($this->_data);
            foreach ($this->_data as $entry) {
                $temp = sscanf($entry, "%s %s %s %s");
                $entry = array();
                $entry['mac_address'] = $temp[1];
                $entry['interface'] = $temp[3];
                if (in_array($entry['interface'], $omit) == false) {
                    array_push($result, $entry);
                } // if
            } // foreach
        } else { // Support for XL-series switches
            $this->_send('show mac-address-table | include Secure|Dynamic');
            $this->_readTo($this->_prompt);
            $this->_data = str_replace('FastEthernet', 'Fa', $this->_data);
            $this->_data = str_replace('GigabitEthernet', 'Gi', $this->_data);
            $this->_data = explode("\r\n", $this->_data);
            for ($i = 0; $i < 3; $i++) array_shift($this->_data);
            array_pop($this->_data);
            foreach ($this->_data as $entry) {
                $temp = sscanf($entry, "%s %s %s %s");
                $entry = array();
                $entry['mac_address'] = $temp[0];
                $entry['interface'] = $temp[3];
                if (in_array($entry['interface'], $omit) == false) {
                    array_push($result, $entry);
                } // if
            } // foreach
        } // if .. else
        $this->_data = $result;
        return $this->_data;
    } // showMacAddressTable

    /**
     * Show IPv6 Neighbor Table (execute an IOS "show ipv6 neighbors" command)
     * 
     * This is the IPv6 equivilant of the ARP table.
     *
     * Result Array:
     * 
     * Array
     * (
     *     [n] => Array
     *         (
     *             [ipv6]
     *             [mac_address]
     *             [age]
     *         )
     * )
     *
     * [..]
     * @return mixed|boolean On success returns an array of associative arrays, false on failure.
     */
    public function showIpv6Neighbors()
    {
        $this->_send('show ipv6 neighbors');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        for ($i = 0; $i < 2; $i++) array_shift($this->_data);
        for ($i = 0; $i < 2; $i++) array_pop($this->_data);
        foreach ($this->_data as $entry) {
            $temp = sscanf($entry, "%s %s %s %s %s");
            $entry = array();
            $entry['ipv6'] = $temp[0];
            $entry['mac_address'] = $temp[2];
            $entry['age'] = $temp[1];
            array_push($result, $entry);
        } // foreach
        $this->_data = $result;
        return $this->_data;
    } // showIpv6Neighbors

    /**
     * Show IPv6 Routers (execute an IOS "show ipv6 routers" command)
     *
     * Used to detect IPv6 RA (Router Advertisement)
     *
     * Result Array:
     * 
     * Array
     * (
     *     [n] => Array
     *         (
     *             [router]
     *             [prefix]
     *         )
     * )
     *
     * [..]
     * @return mixed|boolean On success returns an array of associative arrays, false on failure.
     */
    public function showIpv6Routers()
    {
        $this->_send('show ipv6 routers');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        array_shift($this->_data);
        array_pop($this->_data);
        for ($i = 0; $i < count($this->_data); $i++) {
            $entry = trim($this->_data[$i]);
            if (substr($entry, 0, 7) == 'Router ') {
                $temp = sscanf($entry, "%s %s %s");
                $entry = array();
                $entry['router'] = $temp[1];
                $temp = sscanf(trim($this->_data[$i + 4]), "%s %s %s");
                $entry['prefix'] = $temp[1];
                $i = $i + 5;
                array_push($result, $entry);
            } // if
        } // for
        $this->_data = $result;
        return $this->_data;
    } // showIpv6Routers  

    /**
     * Ping (execute an IOS "ping $host" command)
     * @param  string         $host The hostname or IP address to ping.
     * @return string|boolean On success returns the string output of the command, false on failure.
     */
    public function ping($host) 
    {
        $this->_send("ping $host");
        $this->_readTo($this->_prompt);
        $this->_data = explode("\r\n", $this->_data);
        for ($i = 0; $i < 3; $i++) array_shift($this->_data);
        array_pop($this->_data);
        $this->_data = implode("\n", $this->_data);        
        return $this->_data;
    } // ping

    /**
     * Traceroute (execute an IOS "traceroute $host" command)
     * @param  string         $host The hostname or IP address to trace to.
     * @return string|boolean On success returns the string output of the command, false on failure.
     */
    public function traceroute($host) 
    {
        $this->_send("traceroute $host");
        $this->_readTo($this->_prompt);
        $this->_data = explode("\r\n", $this->_data);
        for ($i = 0; $i < 3; $i++) array_shift($this->_data);
        array_pop($this->_data);
        $this->_data = implode("\n", $this->_data);        
        return $this->_data;
    } // ping

    /**
     * List Trunk Interfaces
     * @return array|boolean On success returns an array, false on failure.
     */
    public function trunkInterfaces() 
    {
        $this->_send('show interface status | include trunk');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        array_shift($this->_data);
        array_pop($this->_data);
        if (count($this->_data) > 0) {
            foreach ($this->_data as $interface) {
                $interface = explode(" ", $interface);
                array_push($result, $interface[0]);
            } // foreach
        } // if
        $this->_data = $result;
        return $this->_data;
    } // trunkInterfaces

    /**
     * List VLANs available through STP
     * @return array|boolean On success returns an array, false on failure.
     */
    public function availableVlans() 
    {
        $this->_send('show spanning-tree summary | include ^VLAN');
        $this->_readTo($this->_prompt);
        $result = array();
        $this->_data = explode("\r\n", $this->_data);
        array_shift($this->_data);
        array_pop($this->_data);
        if (count($this->_data) > 0) {
            foreach ($this->_data as $vlan) {
                $vlan = explode(" ", $vlan);
                $vlan = substr($vlan[0], 4);
                array_push($result, intval($vlan));
            } // foreach
        } // if
        $this->_data = $result;
        return $this->_data;
    } // availableVlans

} // Cisco

// trailing PHP tag omitted to prevent accidental whitespace