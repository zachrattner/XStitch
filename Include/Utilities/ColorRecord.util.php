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
    
require_once('Floss.query.php');

/* The ColorRecord class keeps track of the colors in an image. */
class ColorRecord
{
    /* The 2D array containing the colors. */
    private $Grid;
    
    /* The color information. */
    private $Colors;

    /* Initialize member data to default values. */
    public function __construct()
    {
        $this->Grid   = array();
        $this->Colors = array();
    }
    
    /* Sort the internal array of colors by code and return the result. */
    public function colors()
    {
        /* Operate on a temporary copy of the internal colors array, since the
          actual array is used as a map. */
        $Scratchpad = $this->Colors;
        usort($Scratchpad, 
            function($a, $b)
            {   
                /* If both codes are numeric, then compare numerically (natural
                   order). */
                if (is_numeric($a[Floss::CODE]) && is_numeric($b[Floss::CODE]))
                {
                    return (intval($a[Floss::CODE]) - intval($b[Floss::CODE]));
                }
                
                /* If only the left-hand side is numeric, then place it before
                   the alphabetic one. */
                if (is_numeric($a[Floss::CODE]))
                {
                    return -1;
                }
                
                /* Same as previous case, but for right-hand side. */
                if (is_numeric($b[Floss::CODE]))
                {
                    return 1;
                }
                
                /* If neither value is numeric, compare as strings. */
                return strcmp($a[Floss::CODE], $b[Floss::CODE]);
            });
                
        return $Scratchpad;
    }
    
    /* Return the color at the specific point. */
    public function colorAt($pX, $pY)
    {
        $X = intval($pX);
        $Y = intval($pY);
        
        if (!isset($this->Grid[$X]))
        {
            return null;
        }
        
        if (!isset($this->Grid[$X][$Y]))
        {
            return null;
        }
        
        $ID = $this->Grid[$X][$Y];        
        if (!isset($this->Colors[$ID]))
        {
            return null;
        }
        
        return $this->Colors[$ID];
        
    }
    
    /* Set the color at the specified point. */
    public function setColor($pX, $pY, $pColor)
    {
        $X = intval($pX);
        $Y = intval($pY);
        
        if (!isset($this->Grid[$X]))
        {
            $this->Grid[$X] = array();
        }
        
        $this->addColor($pColor);
        $this->Grid[$X][$Y] = intval($pColor[Floss::ID]);
    }
    
    /* Add a color to the internal list of colors. */
    public function addColor($pColor)
    {
        if (empty($pColor) || in_array($pColor, $this->Colors))
        {
            return false;
        }
        
        $ID = intval($pColor[Floss::ID]);
        $this->Colors[$ID] = $pColor;
        
        /* The ID is the array key, so don't store it as a value. */
        unset($this->Colors[$ID][Floss::ID]);
        
        return true;
    }
}

?>