<?php

/******************************************************************************
 * This file is part of XStitch, a web-based tool for creating cross-stitch 
 * patterns from any image.
 *
 * XStitch was released in December 2011 by Zach Rattner 
 * (info@zachrattner.com).
 *
 * XStitch is free software: you can redistribute it and/or modify
 * it under the terms of the BSD 2-Clause license.
 *
 * Copyright (c) 2011, Zach Rattner
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, 
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice, 
 *    this list of conditions and the following disclaimer in the documentation 
 *    and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 ******************************************************************************/

/* This class is used to retrieve images from remote servers. */
class ImageFetcher
{
    /* The time to wait for a server to respond before giving up, in 
       seconds. */
    const DEFAULT_TIMEOUT = 30;
    
    /* The default port to listen on if none is specified. */
    const DEFAULT_PORT = 80;
    
    /* The maximum number of bytes to read at once from the remote server. */
    const MAX_BYTES = 4096;
    
    /* Permissions applied to the file after retrieval from the remote
       server. */
    const DEFAULT_PERMISSIONS = 0644;
    
    public static function fetch($pSource, $pDestination, $pPort = self::DEFAULT_PORT)
    {
        /* Determine the hostname and path of the remote file. */
        $Host = parse_url($pSource, PHP_URL_HOST);
        $Path = parse_url($pSource, PHP_URL_PATH);
        
        /* Build an HTTP header to request the specified file. */
        $Header = 'GET '    . $Path . ' HTTP/1.1' . "\r\n" . 
                  'Host: '  . $Host               . "\r\n" . 
                  'Connection: Close'             . "\r\n\r\n";
        
        /* Attempt to open a socket to the remote server. */
        $Port         = intval($pPort);
        $ErrorCode    = 0;
        $ErrorMessage = null;
        $Handle       = @fsockopen($Host, $Port, $ErrorCode, $ErrorMessage, self::DEFAULT_TIMEOUT);
        
        /* If the socket could not be opened, then the fetch failed. */
        if (empty($Handle))
        {
            return false;
        }
        
        /* Send the header over the socket */
        fwrite($Handle, $Header);
    
        /* Read the response from the server. */
        $Response = null;
        while(!feof($Handle))
        {
            $Response .= fread($Handle, self::MAX_BYTES);
        }		
        fclose($Handle);
        
        /* Determine the header and the body in the response. */
        $Separator = strpos($Response, "\r\n\r\n");
        $Header    = substr($Response, 0, $Separator);
        $Body      = substr($Response, $Separator + strlen($Separator) + 1);
    
        /* Make sure the HTTP status was 200 (OK). */
        $Lines   = explode("\r\n", $Header);
        $Matches = array();
        preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $Lines[0], $Matches);
        $Status = intval($Matches[2]);
    
        if ($Status == 200)
        {
            /* Attempt to store the body of the response in the given file. */
            $Success = (file_put_contents($pDestination, $Body) > 0);
            
            /* Strip the permissions so nobody does anything silly with it.
               After all, these images will be in the web root. */
            $Success &= chmod($pDestination, self::DEFAULT_PERMISSIONS);
            return $Success;
        }

        return false;
    }
}

?>