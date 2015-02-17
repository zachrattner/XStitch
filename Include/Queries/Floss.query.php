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

require_once('Query.query.php');

/* The Floss class provides an interface to the Floss table. See
 *  Include/SQL/Floss.sql for the definition of the Floss table. 
 */
class Floss extends Query
{
    /* MySQL table name */
    const TABLE_NAME = 'Floss';
    
    /* Columns in the Floss table */
    const ID         = 'FlossID';
    const CODE       = 'FlossCode';
    const RED        = 'FlossRed'; 
    const GREEN      = 'FlossGreen'; 
    const BLUE       = 'FlossBlue';
    const SYMBOL     = 'FlossSymbol';
    
    /* Load the color closest to the given point. */
    public static function loadClosest($pRed, $pGreen, $pBlue)
    {
        /* Sanitize the input fields. */
        $Red   = intval($pRed);
        $Green = intval($pGreen);
        $Blue  = intval($pBlue);
        
        /* Build the query. */
        $Query = sprintf
        (
            'SELECT * FROM %s ' . 
                'ORDER BY (POW((%s - %u), 2) + POW((%s - %u), 2) + POW((%s - %u), 2)) ASC ' . 
                'LIMIT 1',
            self::TABLE_NAME,
            self::RED,
            $Red,
            self::GREEN,
            $Green,
            self::BLUE,
            $Blue
        );
        
        /* Fetch the result. */
        $Result = self::exec($Query);
        if (empty($Result))
        {
            return null;
        }
        
        return $Result[0];
    }
}

?>