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
require_once('ColorRecord.util.php');

class Image
{
    /* Supported image types. If PHP supported enums, this would be an enum. */
    const GIF = 0;
    const JPG = 1;
    const PNG = 2;
    
    /* Default page dimensions. */
    const ROWS_PER_PAGE                = 50;
    const COLUMNS_PER_PAGE             = 50;
    const DEFAULT_THREAD_TABLE_COLUMNS = 4;
    
    /* Minimum/default/maximum values for numerical input parameters. */
    const MIN_COLORS     = 1;
    const DEFAULT_COLORS = 50;
    const MAX_COLORS     = 100;
    const MIN_WIDTH      = 10;
    const DEFAULT_WIDTH  = 200;
    const MAX_WIDTH      = 1000;
    const MIN_HEIGHT     = 10;
    const DEFAULT_HEIGHT = 200;
    const MAX_HEIGHT     = 1000;
    
    /* Local path to the image file. */
    private $Path;
    
    /* GD image instance. */
    private $Image;
    
    /* Type of the image, element of {self::GIF, self::JPG, self::PNG} */
    private $Type;
    
    /* Image dimensions. */
    private $Width;
    private $Height;
    
    /* Number of colors in image, */
    private $Colors;
    
    /* Human-readable array of error strings. */
    private $Errors;
    
    /* Maximum limits. */
    private $MaxColors;
    
    /* ColorRecord instance. */
    private $Record;
    
    /* Boolean flag that indicates whether the image has already been analyzed.
       Analysis takes a long amount of time (on the order of tens of seconds),
       so it should not be done unless absolutely necessary. */
    private $Analyzed;
    
    /* Initialize member data. */
    public function __construct
    (
        $pPath,
        $pMaxColors = self::MAX_COLORS
    )
    {
        $this->Path      = $pPath;
        $this->Width     = 0;
        $this->Height    = 0;
        $this->Colors    = intval($pMaxColors);
        $this->Errors    = array();
        $this->Record    = new ColorRecord();
        $this->Analyzed  = false;
        $this->MaxColors = intval($pColors);
        
        /* Determine the extension from the image path. */
        $ExtensionIndex  = strrpos($pPath, '.');
        $Extension       = strip_tags(strtolower(substr($pPath, $ExtensionIndex + 1)));
        
        /* Call the appropriate GD function based on the extension. */
        switch ($Extension)
        {
            case 'jpg':
            case 'jpeg':
                $this->Type  = self::JPG;
                $this->Image = imagecreatefromjpeg($pPath);
                break;
                
            case 'gif':
                $this->Type  = self::GIF;
                $this->Image = imagecreatefromgif($pPath);
                break;
                
            case 'png':
                $this->Type  = self::PNG;
                $this->Image = imagecreatefrompng($pPath);
                break;
            
            default:
                $this->Errors[] = 'Unrecognized file extension: ' . $Extension;
                return;
        }
        
        /* Ensure that the image was loaded. */
        if (empty($this->Image))
        {
            $this->Errors[] = 'Unable to load image.';
            return;
        }
        
        /* Attempt to read image metadata to store the height and width. */
        $Params = getimagesize($pPath);
        if (empty($Params))
        {
            $this->Errors[] = 'Unable to read image metadata.';
            return false;
        }
        
        $this->Width  = intval($Params[0]);
        $this->Height = intval($Params[1]);
        
        /* Reduce the color palette without dithering. */
        $this->reduce($this->Colors, false);
    }
    
    /* Reduce the color palette to at most the given amount of colors.
       Dithering is optional. */
    public function reduce($pColors, $pDither = false)
    {
        /* Sanitize parameters. */
        $Dither = (intval($pDither) != 0);
        $Colors = intval($pColors);
                
        /* Create a scratchpad true color image, then convert the original to
           a palette image. */
        $Scratch = imagecreatetruecolor($this->Width, $this->Height);
        
        if (empty($Scratch))
        {
            return false;
        }
        
        if (!imagecopymerge(
                $Scratch, 
                $this->Image, 
                0, 0, 0, 0, 
                $this->Width, 
                $this->Height, 
                100
            ))
        {
            return false;
        }
        
        if (!imagetruecolortopalette($this->Image, $Dither, $Colors))
        {
            return false;
        }
        
        /* Update the palette so the colors match the true color original more
           closely. */
        if (!imagecolormatch($Scratch, $this->Image))
        {
            return false;
        }
        
        /* The image has been revised, so free the scratchpad. */
        if (!imagedestroy($Scratch))
        {
            return false;
        }
        
        return true;
    }
    
    /* Return true if and only if the current instance has encountered at least
       one error. */
    public function hasErrors()
    {
        return (!empty($this->Errors));
    }
    
    /* Return the human-readable string of errors that the instance has
       encountered. */
    public function errors()
    {
        return $this->Errors;
    }
    
    /* Resize the image such that neither dimension is larger than the given
       parameters (in pixels), but retain the original aspect ratio. */
    public function resizeToMax($pWidth, $pHeight)
    {
        $Width  = intval($pWidth);
        $Height = intval($pHeight);
    
        $XRatio = floatval($Width  / $this->Width);
        $YRatio = floatval($Height / $this->Height);
        
        $Ratio  = min($XRatio, $YRatio);
        
        $NewWidth  = intval(round($this->Width  * $Ratio));
        $NewHeight = intval(round($this->Height * $Ratio));
        
        return $this->resize($NewWidth, $NewHeight);
    }
    
    /* Scale the image by the specified percentage. */
    public function resizeScale($pPercentage)
    {
        $Width  = intval(round($this->Width  * floatval($pPercentage)));
        $Height = intval(round($this->Height * floatval($pPercentage)));
        
        return $this->resize($Width, $Height);
    }
    
    /* REsize the image to the specified dimensions, in pixels. */
    public function resize($pWidth, $pHeight)
    {
        $Width    = intval($pWidth);
        $Height   = intval($pHeight);
        $NewImage = imagecreatetruecolor($Width, $Height);
        
        $Success = imagecopyresampled
        (
            $NewImage, 
            $this->Image, 
            0, 
            0, 
            0, 
            0, 
            $Width, 
            $Height, 
            $this->Width, 
            $this->Height
        );
        
        if ($Success)
        {
            imagedestroy($this->Image);
            
            $this->Image  = $NewImage;
            $this->Width  = $Width; 
            $this->Height = $Height;
        }

        return $Success;
    }
    
    /* Round each pixel in the image to the nearest color. */
    public function analyze()
    {
        /* Only analyze each instance once. */
        if ($this->Analyzed)
        {
            return false;
        }
        
        /* Loop down, then across (X, then Y). */
        for ($i = 0; $i < $this->Height; $i++)
        {
            for ($j = 0; $j < $this->Width; $j++)
            {
                /* Determine the pixel color at this location. */
                $Color = imagecolorsforindex($this->Image, imagecolorat($this->Image, $j, $i));
                
                /* FInd the closest floss color. */
                $Floss = Floss::loadClosest
                (
                    $Color['red'],
                    $Color['green'],
                    $Color['blue']
                );
                
                if (empty($Floss))
                {
                    $this->Errors[] = sprintf
                    (
                        'Could not find color closest to rgb(%d, %d, %d).',
                        $Color['red'],
                        $Color['green'],
                        $Color['blue']
                    );
                    continue;
                }

                /* Store the floss color in the color record. */
                $this->Record->setColor($j, $i, $Floss);
            }
        }
        
        $this->Analyzed = true;
    }
    
    /* Generate an HTML preview of the cross-stitch pattern. */
    public function generatePreview($pWrapperWidth = 880, $pOutput = false)
    {
        /* This function can only be called once the image has been
           analyzed. */
        if (!$this->Analyzed)
        {
            $this->analyze();
        }   
        
        /* Add "Small" or "Large" CSS classes to the pixels depending on the
           image size. This way, smaller image can have larger pixels. */
        $WrapperWidth = intval($pWrapperWidth);
        $Class = '';
        if ($this->Width <= floor($WrapperWidth / 4))
        {
            $Class = 'class="Large"';
        }
        else if ($this->Width >= floor($WrapperWidth / 2))
        {
            $Class = 'class="Small"';
        }
        
        $HTML  = '<table cellpadding="0" cellspacing="0">';
        
        /* Build a table, locating the color from the record for each pixel. */
        for ($i = 0; $i < $this->Height; $i++)
        {
            $HTML .= '<tr>';
            for ($j = 0; $j < $this->Width; $j++)
            {
                $Color = $this->Record->colorAt($j, $i);
                $HTML .= 
                    sprintf
                    (
                        '<td %s style="background-color: rgb(%d, %d, %d);"></td>',
                        $Class,
                        $Color[Floss::RED],
                        $Color[Floss::GREEN],
                        $Color[Floss::BLUE]
                    );
            }
            $HTML .= '</tr>';
        }
        
        $HTML .= '</table>';
        
        /* Output directly to the browser if requested. */
        if ($pOutput)
        {
            echo $HTML;
        }
        
        return $HTML;
    }
    
    /* Generate an HTML floss color table list. */
    public function generateFlossTable()
    {
        $HTML = '<table id="FlossTable">';
        
        $i = 1;
        
        $Colors = $this->Record->colors();
        $Length = count($Colors);
        
        /* The number of columns is fixed, but the number of rows is a function
           of the number of columns and colors. */
        $Columns = self::DEFAULT_THREAD_TABLE_COLUMNS;
        $Rows    = intval(ceil($Length / $Columns));

        $Keys = array_keys($Colors);        
        for ($i = 0; $i < $Rows; $i++)
        {
            $HTML .= '<tr>';
            for ($j = 0; $j < $Columns; $j++)
            {
                $Index = ($Columns * $i) + $j;

                /* If the end of the color list is reached before the last row
                   has been completed, exit out of the loop. */
                if (!isset($Keys[$Index]))
                {
                    break;
                }
                
                $Color = $Colors[$Keys[$Index]];
                
                $HTML .= '<td class="Code">' . $Color[Floss::CODE] . '</td>';
                $HTML .= sprintf
                         (
                             '<td class="Swatch" style="background-color: rgb(%d, %d, %d);"></td>',
                             $Color[Floss::RED],
                             $Color[Floss::GREEN],
                             $Color[Floss::BLUE]
                         );                            
            }
            $HTML .= '</tr>';
        }
        
        $HTML .= '</table>';
        return $HTML;
    }
    
    /* Save the image to the path it was loaded from. */
    public function save()
    {
        return $this->output($this->Path);
    }
    
    /* Save an image to the specified path, and optionally free the GD object's
       memory. */
    public function output($pFilename, $pDestroy = false)
    {    
        $Success = false;
       
       /* Call the appropriate output function based on the image's type. */
        switch ($this->Type)
        {
            case self::GIF:
                $Sucess = imagegif($this->Image, $pFilename);
                break;
                
            case self::JPG:
                $Sucess = imagejpeg($this->Image, $pFilename);
                break;
                
            case self::PNG:
                $Sucess = imagepng($this->Image, $pFilename);
                break;
                
            default:
                $this->Errors[] = 'Unrecognized type: ' . $this->Type;
                break;
        }
        
        if ($pDestroy)
        {
            $Success &= imagedestroy($this->Image);
        }
        
        return $Success;
    }
    
    /* Attempt to generate an HTML pattern and save it in the specified output file. */
    public function generatePattern($pOutput, $pImageName)
    {
        $HTML = '';
        
        /* Define the doctype as an HTML 5 page, import CSS and JS. */
        $HTML .= '<!doctype html>' . "\n";
        $HTML .= '<html lang="en-us">' . "\n";
        $HTML .= '<head>' . "\n";
        $HTML .=    '<meta charset="utf-8">' . "\n";
        $HTML .=    '<link rel="stylesheet" type="text/css" href="../CSS/Pattern.min.css" />' . "\n";
        $HTML .=    '<script type="text/javascript" src="../Javascript/Pattern.min.js"></script>';
        $HTML .= '</head>' . "\n";
        $HTML .= '<body>' . "\n";
        
        /* Generate the cover page. */
        $Colors     = $this->Record->colors();        
        $ColorCount = count($Colors);
        
        $HTML .= '<div id="CoverPage">';
        $HTML .=    '<img src="' . $pImageName . '" alt="" />';
        $HTML .=    '<p>';
        $HTML .=         $this->Width . ' by ' . $this->Height . ' stitches<br />';
        $HTML .=         $ColorCount . ' thread colors<br />';
        $HTML .=        'Generated at xstitch.zachrattner.com';
        $HTML .=    '</p>';
        $HTML .= '</div>';
        
        /* Two nested loops take care of printing a single page "for each
           column, for each row". But in order to print all the pages in the
           pattern, it is necessary to keep track of the row and offset index
           from the previous iteration. */
        $RowOffset    = 0;
        $ColumnOffset = 0;
        
        /* Determine how many pages wide and tall the pattern is. */
        $VerticalPages   = intval(ceil($this->Height / self::ROWS_PER_PAGE));
        $HorizontalPages = intval(ceil($this->Width  / self::COLUMNS_PER_PAGE));
        
        for ($VerticalPage = 0; $VerticalPage < $VerticalPages; $VerticalPage++)
        {
            $ColumnOffset = 0;
            for ($HorizontalPage = 0; $HorizontalPage < $HorizontalPages; $HorizontalPage++)
            {           
                $HTML .= '<table>';
                
                /* Build the header row. */
                $HTML .= '<tr>';
                
                /* The last column may not be at the maximum limit if this is a
                   rightmost page. */
                $ColumnLimit = intval(min($this->Width - $ColumnOffset, self::COLUMNS_PER_PAGE));
                
                for ($j = 0; $j <= $ColumnLimit; $j++)
                {
                    $HTML .= '<td class="Header">';
                    
                    /* Print row numbers every 10 stitches. */
                    if ($j && !($j % 10))
                    {
                        $Index = $j + $ColumnOffset;
                        $HTML .= $Index;
                    }
                    
                    $HTML .= '</td>';
                }
                $HTML .= '</tr>';
                
                /* The last row may not be at the maximum limit if this is a
                   bottom page. */
                $RowLimit = intval(min($this->Height - $RowOffset, self::ROWS_PER_PAGE));
                for ($i = 0; $i < $RowLimit; $i++)
                {
                    $HTML .= '<tr>';
                
                    /* Build the header column. */
                    $HTML .= '<td class="Header">';    
                    if (!(($i + 1) % 10))
                    {
                        /* Print column numbers every 10 stitches. */
                        $Index = $i + $RowOffset + 1;
                        $HTML .= $Index;
                    }
                    $HTML .= '</td>';
                    
                    for ($j = 0; $j < $ColumnLimit; $j++)
                    {
                        /* Determine the margins for the current cell. Every
                           10th cell in either direction has a thicker right
                           and bottom border. */
                        $Right  = 'border-right:  1px solid #000';
                        $Bottom = 'border-bottom: 1px solid #000'; 
                        
                        if (!(($j + 1) % 10) && ($j != ($ColumnLimit - 1)))
                        {
                            $Right = 'border-right: 2px solid #000';
                        }
                        
                        if (!(($i + 1) % 10) && ($i != ($RowLimit - 1)))
                        {
                            $Bottom = 'border-bottom: 2px solid #000';
                        }
                        
                        /* Convert loop indices to absolute indices via the
                           row/column offsets. */
                        $AbsX  = $ColumnOffset + $j;
                        $AbsY  = $RowOffset    + $i;
                                                
                        $Color = $this->Record->colorAt($AbsX, $AbsY);
                        
                        $HTML .= sprintf
                        (
                            '<td style="%s; %s;"><img src="../Images/Symbols/%s.png" alt="" /></td>',
                            $Right,
                            $Bottom,
                            $Color[Floss::SYMBOL]
                        );
                        
                    }
                    $HTML .= '</tr>';
                }
                
                $HTML .= '</table>';
        
                $ColumnOffset += self::COLUMNS_PER_PAGE;
            }
            $RowOffset += self::ROWS_PER_PAGE;
        }
        
        /* Now, print the thread symbol table. */
        $Rows = intval(ceil(count($Colors) / self::DEFAULT_THREAD_TABLE_COLUMNS));
        
        $HTML .= '<div id="ThreadWrapper">';
        $HTML .= '<table id="Thread">';
        for ($i = 0; $i < $Rows; $i++)
        {
            $HTML .= '<tr>';
            for ($j = 0; $j < self::DEFAULT_THREAD_TABLE_COLUMNS; $j++)
            {
                $Index = (self::DEFAULT_THREAD_TABLE_COLUMNS * $i) + $j;
        
                if (!isset($Colors[$Index]))
                {
                    break;
                }

                $HTML .= '<td class="Symbol"><img src="../Images/Symbols/' . $Colors[$Index][Floss::SYMBOL] . '.png" alt="" /></td>';
                $HTML .= '<td class="Code">' .     $Colors[$Index][Floss::CODE]   . '</td>';
            }
            
            $HTML .= '</tr>';
        }
                
        $HTML .= '</table>';
        $HTML .= '</div>';
        $HTML .= '</body>';
        $HTML .= '</html>';
        
        return (file_put_contents($pOutput, $HTML) !== false);
    }
}

?>