 <?php 
        //Data, connection, auth
        //$dataFromTheForm = $_POST['fieldName']; // request data from the form
        $soapUrl = "https://api.mindbodyonline.com/0_5/SiteService.asmx"; // asmx URL of WSDL
        $soapUser = "username";  //  username
        $soapPassword = "password"; // password

        // xml post structure

        $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns="http://clients.mindbodyonline.com/api/0_5">
                <soapenv:Header/>
                <soapenv:Body>
                   <GetSites>
                      <Request>
                         <SourceCredentials>
                            <SourceName>AccelerantStudiosLLC</SourceName>
                            <Password>oMh1ajTlIwhtxZsHomLprIdxS9Q=</Password>
                            <SiteIDs>
                               <int>0</int>
                            </SiteIDs>
                         </SourceCredentials>
                         <XMLDetail>Full</XMLDetail>
                         <PageSize>10</PageSize>
                         <CurrentPageIndex>0</CurrentPageIndex>
                      </Request>
                   </GetSites>
                </soapenv:Body>
             </soapenv:Envelope>';  

           $headers = array(
                        "Content-type: text/xml;charset=\"utf-8\"",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                        "SOAPAction: http://clients.mindbodyonline.com/api/0_5/GetSites", 
                        "Content-length: ".strlen($xml_post_string),
                    ); 

            $url = $soapUrl;

            // PHP cURL  for https connection with auth
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $soapUser.":".$soapPassword); // username and password - declared at the top of the doc
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
            print_r($response2);
            // convertingc to XML
            $parser = simplexml_load_string($response2);
            // user $parser to get your data out of XML response and to display it.
    ?>