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
 
/* The Query class abstracts PHP's mysql_* functions. */
class Query
{
    /* The remote server to connect to. */
    const HOSTNAME = 'place-hostname-here';
    
    /* The MySQL user to connect as. */
    const USERNAME = 'place-username-here';
    
    /* The password for the given MySQL user. */
    const PASSWORD = 'place-password-here';
    
    /* The database to select once authenticated. */
    const DATABASE = 'place-database-here';
    
    /* Connection handle. */
    private static $Connection = null;
    
    /* Attempt to connect to a database with authentication. */
    public static function connect
    (
        $pHostname = self::HOSTNAME,
        $pUsername = self::USERNAME,
        $pPassword = self::PASSWORD,
        $pDatabase = self::DATABASE
    )
    {   
        self::$Connection = mysql_connect($pHostname, $pUsername, $pPassword);
        
        if (!empty(self::$Connection))
        {
            return mysql_select_db($pDatabase);
        }
        
        return false;
    }
    
    /* Execute a MySQL query, assuming it has already been sanitized. */
	public static function exec($pQuery)
	{
	    /* Attempt to connect if not already connected. */
	    if (empty(self::$Connection))
	    {
	        self::connect();
	        
	        if (empty(self::$Connection))
	        {
	            return false;
	        }
	    }
	    

		$Result = mysql_query($pQuery, self::$Connection);
		
		if (is_bool($Result))
		{
			return $Result;
		}

        /* If the result was not a boolean, then data was selected. */
		$ResponseData = array();
		while ($Row = mysql_fetch_assoc($Result))
		{
			$ResponseData[] = $Row;
		}
		return $ResponseData;
	}
	
	/* Return the ID of the last inserted row. */
	public static function insertID()
	{
	    if (empty(self::$Connection))
	    {
	        return 0;
	    }
	    
		return mysql_insert_id(self::$Connection);
	}
	
	/* Return the number of rows affected by the last query executed. */
	public static function affectedRows()
	{
	    if (empty(self::$Connection))
	    {
	        return 0;
	    }
	    
	    return mysql_affected_rows(self::$Connection);
	}
	
	/* Return the error associated with the last query executed. */
	public static function error()
	{
	    if (empty(self::$Connection))
	    {
	        return null;
	    }
	    
		return mysql_error(self::$Connection);
	}
}

?>