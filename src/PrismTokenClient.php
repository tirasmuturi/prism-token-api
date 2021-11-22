#!/usr/bin/env php
<?php
// Minimal example of using the PrismToken Thrift API (over TLS):
// - Connects to hard-coded host+port using SSL/TLS (trusting the self-signed certificate)
// - Constructs a TokenApi client instance using the appropriate Thrift framing and protocol
// - With the TokenApi client: signs in to PrismToken and issues a credit token
//
// To run this example you will need the generated sources for the PrismToken
// Thrift API (Prism\PrismToken1), and the Thrift libraries on which they depend.
// To run: c:\php-5.6.40\php.exe PrismTokenClient.php

error_reporting(E_ALL);

// Load PrismToken and Thrift libraries
  require_once __DIR__.'/Thrift/ClassLoader/ThriftClassLoader.php';
  use Thrift\ClassLoader\ThriftClassLoader;
  $loader = new ThriftClassLoader();
  $loader->registerNamespace('Thrift', __DIR__);
  $loader->registerDefinition('Prism\PrismToken1', __DIR__);
  $loader->register();
  
  use Thrift\Transport\TSocket;
  use Thrift\Transport\TFramedTransport;
  use Thrift\Protocol\TBinaryProtocol;
  use Thrift\Exception\TException;
  use Prism\PrismToken1\TokenApiClient;

// Connection options
  $host = "196.214.189.218";
  $port = 9443;
  $username = "ptapiuser";
  $password = "Ptapiuser1";


// Create a PrismToken Thrift client
  // Note 1: The Prism NSS does not know its DNS name and the certificate is self-signed.
  //   In PRODUCTION you should import the certificate from the NSS into your trusted store,
  //     and only bypass peer name verification.
  //   In DEVELOPMENT it is convenient to allow self-signed certificates.
  //
  //   To do this we cannot use TSocket->open(), but must instead use stream_socket_client(),
  //     using a hostname with prefix 'tlsv1.2://', and a context that allows self-signed
  //     certificates and disables peer name checking.
  // 
  //   REF http://php.net/manual/en/migration56.openssl.php
  //   REF https://i.justrealized.com/2009/allowing-self-signed-certificates-for-pfsockopen-and-fsockopen/
  //   REF https://stackoverflow.com/questions/41934329/how-to-make-fsockopen-to-ignore-certificates-when-using-tls
  //
  // Note 2: We require a modified TSocket.php that uses fwrite/fread rather than
  //   stream_socket_sendto/stream_socket_recvfrom - the latter don't work with
  //   a SSL/TLS socket.
  //
  //   REF https://stackoverflow.com/questions/45384945/difference-betweens-php-socket-i-o-functions
  //   REF comments on https://issues.apache.org/jira/browse/THRIFT-948
  print "\nConnecting to host='" . $host . "' port='" . $port . "'\n";
  $context = stream_context_create([
    'ssl' => [
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
  ]);
  $TLSPREFIX = "tlsv1.2://"; 
  $socket = stream_socket_client($TLSPREFIX.$host.":".$port, $errno, $errstr, ini_get("default_socket_timeout"),
    STREAM_CLIENT_CONNECT, $context);
  if ($socket === FALSE) {
    throw new TException('TSocket: Could not connect');
  }
  $trans = new TSocket($TLSPREFIX.$host, $port);
  $trans->setHandle($socket);
  $trans->setSendTimeout(1000);
  $trans->setRecvTimeout(5000);
  $trans = new TFramedTransport($trans);
  print "-> Connected\n";
  $proto = new TBinaryProtocol($trans);
  $ptoken = new TokenApiClient($proto);
  print "-> PrismToken client created\n";


// Helper
  function generateMessageId() {
    return sprintf('%10d-%s', time(), bin2hex(openssl_random_pseudo_bytes(8)) );
  }


// Basic comms test
  print "\nPrismToken Ping()\n";
  $pingResp = $ptoken->ping(0, "Hello, world!");
  print "-> " . $pingResp . "\n";


// Sign in
  print "\nPrismToken SignInWithPassword()\n";
  $resp1 = $ptoken->signInWithPassword(generateMessageId(), "local", $username, $password, new \Prism\PrismToken1\SessionOptions()); 
  $accessToken = $resp1->accessToken;
  print "-> OK\n";


// Issue a 100kW Electricity token to a meter
  print "\nPrismToken IssueCreditToken()\n";
  $meter = new \Prism\PrismToken1\meterConfigIn([ 'drn' => "00000000000", 'sgc' => 123456,
    'krn' => 1, 'ti' => 1, 'ea' => 7, 'tct' => 1, 'ken' => 255, 'allowKrnUpdate' => false ]);
  $tokens = $ptoken->issueCreditToken(generateMessageId(), $accessToken, $meter, 0, 100.0 * 10, 0, 0);
  foreach ($tokens as $t) {
    print "-> " . print_r($t, TRUE) . "\n";
  }


// Done
  print "\nDone\n";
  $trans->close();
  
?>
