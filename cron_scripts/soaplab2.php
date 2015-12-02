 <?php 
        //Data, connection, auth
        //$dataFromTheForm = $_POST['fieldName']; // request data from the form
        $soapUrl = "https://api.mindbodyonline.com/0_5/SiteService.asmx"; // asmx URL of WSDL
        $soapUser = "username";  //  username
        $soapPassword = "password"; // password

        // xml post structure

        $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:_5="http://clients.mindbodyonline.com/api/0_5">
               <soapenv:Header/>
               <soapenv:Body>
                  <_5:GetLocations>
                     <_5:Request>
                        <_5:SourceCredentials>
                           <_5:SourceName>AccelerantStudiosLLC</_5:SourceName>
                           <_5:Password>oMh1ajTlIwhtxZsHomLprIdxS9Q=</_5:Password>
                           <_5:SiteIDs>
                              <_5:int>-99</_5:int>
                           </_5:SiteIDs>
                        </_5:SourceCredentials>
                        <_5:XMLDetail>Full</_5:XMLDetail>
                        <_5:PageSize>10</_5:PageSize>
                        <_5:CurrentPageIndex>0</_5:CurrentPageIndex>
                     </_5:Request>
                  </_5:GetLocations>
               </soapenv:Body>
             </soapenv:Envelope>';
             
           $headers = array(
                        "Content-type: text/xml;charset=\"utf-8\"",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        "SOAPAction: http://clients.mindbodyonline.com/api/0_5/GetLocations", 
                        "Content-length: ".strlen($xml_post_string),
                    ); 

            $url = $soapUrl;

            // PHP cURL  for https connection with auth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // converting
            $response = curl_exec($ch); 
            curl_close($ch);

            // converting
            $response1 = str_replace("<soap:Body>","",$response);
            $response2 = str_replace("</soap:Body>","",$response1);
            echo"<pre>";
            print ($response2);
            // convertingc to XML
            $parser = simplexml_load_string($response2);
            // user $parser to get your data out of XML response and to display it.
    ?>