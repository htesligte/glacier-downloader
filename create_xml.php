<?php
/**
 * Created by PhpStorm.
 * User: henk
 * Date: 6-5-15
 * Time: 20:00
 */
/*
 * Archive Id: k2uyWRhgRgkv_YeO5A1NIX-g8e9ohOCNLZOkFUgXM-9r7Ega7ZokjuYz5vYQzp9FInbz0cXdjpYbaJ-x94cCIrO6sLyW9Wqjk9hpgWNJi8d8bQKK6ABu0FdaevEG5UFFg8gX8paf0g
 * Size: 10,00 MB (10485760 bytes)
 */
// ik wil max 200mb per uur downloaden ( nu even 2mb )
//  dus moet het geheel verdeeld worden in stukken van 2mb en dan per uur gescheduled worden
require 'vendor/autoload.php';

define( 'FILE_SIZE', 273804165120 );
define( 'PART_SIZE', 32*1024*1024 );
define( "FILE_ID", "lmI6zPL6nDa9yksbXn9VJID7HVdY4kotSWlT1vKp2y0VOaN2ml4ZorckFLP3IGFw1Yb7SuwmIkDohXtDz_bVmnBZSN1Euj3GNAuBBIozMiN4ydsxvM17fayVnTP7EaN1e__Mt4MQGQ" );

$doc = new DOMDocument('1.0');
$doc->formatOutput = true;
$root = $doc->createElement( 'root' );
$root = $doc->appendChild( $root );
$em = $doc->createElement( 'file' );
$text = $doc->createTextNode( FILE_ID );
$em->appendChild( $text );
$root->appendChild( $em );


$partsize = 0;
$timestamp = time()+600;
$continue = true;
while( $continue )
{
    $nextPartSize = $partsize + PART_SIZE-1;
    if( ( $nextPartSize+1 ) >= FILE_SIZE )
    {
        $nextPartSize = FILE_SIZE - 1;
        $continue = false;
    }

    $parts[] = array(
        'start' => $partsize,
        'end' => $nextPartSize,
        'schedule_time' => date( 'c', $timestamp ),
        'start_time' => '',
        'jobId' => '',
        'download_hash' => '',
        'running' => 0,
        'finished' => 0,
    );

    $timestamp += ( 60 * 60 );
    $partsize += PART_SIZE;
};

foreach( $parts as $part )
{
    $em = $doc->createElement('part' );
    foreach( $part as $key=>$value )
    {
        $child = $doc->createElement( $key );
        $text_node = $doc->createTextNode( $value );
        $child->appendChild( $text_node );
        $em->appendChild( $child );
    }
    $root->appendChild( $em );
}

$doc->save( 'hdd.xml' );