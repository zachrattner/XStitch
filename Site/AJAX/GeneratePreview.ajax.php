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
 
/* This script assumes the following directives have been set in php.ini. If they haven't, you can 
 * set them in the script using ini_set.
 *
 * memory_limit="512M"
 * max_execution_time="300"
 * 
 * Append the following to include_path: 
 * ./Include/Queries:../Include/Queries:./Include/Utilities:../Include/Utilities:../../Include/Queries:../../Include/Utilities'
 */

require_once('ImageFetcher.util.php');
require_once('Image.util.php');

/* The maximum number of times to attempt to create a unique file name before
   giving up. */
define('MAX_OUTPUT_FILE_CREATION_ATTEMPTS', 100);

/* Initialize a pessimistic response object. */
$ResponseData = array
(
    'Success' => false,
    'Message' => 'There was an error processing your request right now. Please try again later.'
);

/* Import parameters. */
$WebLink = $_POST['WebLink'];
$Width   = intval($_POST['Width']);
$Height  = intval($_POST['Height']);
$Colors  = intval($_POST['Colors']);

/* Validate the data again, in case it was tampered with. */
if (($Colors < Image::MIN_COLORS) || ($Colors > Image::MAX_COLORS))
{
    $Colors = Image::DEFAULT_COLORS;
} 

if (($Width < Image::MIN_WIDTH) || ($Width > Image::MAX_WIDTH))
{
    $Width = Image::DEFAULT_WIDTH;
}

if (($Height < Image::MIN_HEIGHT) || ($Height > Image::MAX_HEIGHT))
{
    $Height = Image::DEFAULT_HEIGHT;
}

/* The slider provides an integer from 1 to 100. Map that value from 1 to 200
   for the Image class. */
$Colors = intval(round(($Colors / 100) * 200));

/* Extract the extension of the given file ("gif", "jpg", "png", etc.) */
$ExtensionIndex  = strrpos($WebLink, '.');
$Extension       = strip_tags(strtolower(substr($WebLink, $ExtensionIndex + 1)));

/* Attempt to create a unique file to store the image in locally. */
$File = null;
$Attempts = 0;
do
{
    $File = '../Patterns/' . sha1(rand()) . sha1(rand()) . '.' . $Extension;
    $Attempts++;
}
while (($Attempts < MAX_OUTPUT_FILE_CREATION_ATTEMPTS) && file_exists($File));

/* If the file still exists, then the attempts have been exhausted. */
if (file_exists($File))
{
    die(json_encode($ResponseData));
}

/* Verify that the remote link is set before attempting to access it. */
if (empty($WebLink))
{
    /* If file uploads were supported, the code to handle it would go here. */
    die(json_encode($ResponseData));
}
else
{
    /* Attempt to save the given image into the given file. */
    $Result = ImageFetcher::fetch($WebLink, $File);
    if (!$Result)
    {
        $ResponseData['Message'] = 'Failed to load image. Please make sure the link is correct.';
        die(json_encode($ResponseData));
    }
}

/* Now, resize the image. */
$Image = new Image($File);
$Image->resizeToMax($Width, $Height);

/* Limit the number of colors to a maximum of the given number. */
$Image->reduce($Colors);

/* Check for errors. */
if ($Image->hasErrors())
{
    $ResponseData['Message'] = implode('<br />', $Image->errors());
    die(json_encode($ResponseData));
}

/* Round each pixel to the nearest color. */
$Image->analyze();

/* Check for errors. */
if ($Image->hasErrors())
{
    $ResponseData['Message'] = implode('<br />', $this->errors());
    die(json_encode($ResponseData));
}

/* Delete the uploaded file, since it's not needed anymore. */
unlink($File);

/* Populate the response object with the analyzed data. */
$ResponseData['Success'] = true;
$ResponseData['Preview'] = $Image->generatePreview();
$ResponseData['Table']   = $Image->generateFlossTable();
$ResponseData['Message'] = 'Feel free to tweak the limits if the preview doesn&#39;t look quite right yet.';

/* Output the response data in JSON. */
echo json_encode($ResponseData);

?>