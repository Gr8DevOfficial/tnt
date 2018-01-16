<?php

/**
 * Shipping Service
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 * @license https://github.com/200MPH/tnt/blob/master/LICENCE MIT
 */

namespace thm\tnt_ec\service\ShippingService;

use thm\tnt_ec\service\AbstractService;
use thm\tnt_ec\service\ShippingService\entity\Address;
use thm\tnt_ec\service\ShippingService\entity\Collection;
use thm\tnt_ec\service\ShippingService\entity\Consignment;
use thm\tnt_ec\service\ShippingService\ShippingResponse;

class ShippingService extends AbstractService {
    
    /* Version */
    const VERSION = 3.0;
    
    /* Service URL */
    const URL = 'https://express.tnt.com/expressconnect/shipping/ship';
        
    /**
     * @var Address
     */
    private $sender;
    
    /**
     * @var Collection
     */
    private $collection;
    
    /**
     * @var Consignment[]
     */
    private $consignment = [];
    
    /**
     * User defined XML content - custom
     * @var bool
     */
    private $userXml = false;
    
    /**
     * Set sender address
     * 
     * @return Address
     */
    public function setSender()
    {
        
        $this->sender = new Address();
        
        return $this->sender;
        
    }
    
    /**
     * Set collection.
     *  
     * @return Collection
     */
    public function setCollection()
    {
        
        $this->collection = new Collection($this->sender);
        
        return $this->collection;
        
    }
    
    /**
     * Add consignment.
     * TNT allow to add up to 50 consignments, but they recommend add 3 per request. 
     * 
     * @return Consignment
     */
    public function addConsignment()
    {
        
        $con = new Consignment();
        $con->setAccount($this->account, $this->accountCountryCode);
        $this->consignment[] = $con;
        
        return end($this->consignment);
        
    }
    
    /**
     * Get shipping service URL
     * @return string
     */
    public function getServiceUrl()
    {
    
        return self::URL;
        
    }

    /**
     * Send request to TNT
     * 
     * @return ShippingResponse
     */
    public function send()
    {
        
        return new ShippingResponse($this->sendRequest(), $this->getXmlContent);
        
    }
    
    /**
     * Set XML content.
     * This is useful when you want to send your own prepared XML document.
     * 
     * @param string $xml
     * @return bool
     */
    public function setXmlContent($xml)
    {
        
        $this->userXml = true;
        return parent::setXmlContent($xml);
        
    }
    
    /**
     * Get XML content
     * 
     * @return string
     */
    protected function getXmlContent()
    {
        
        if($this->userXml === true) {
            
            // return user defined content without modifications
            return parent::getXmlContent();
            
        }
        
        $this->startDocument();
        
        $this->buildSenderSection();
        $this->buildConsignmentSection();
        $this->buildActivitySection();
        
        $this->endDocument();
        
        return $this->xml->outputMemory(false);
        
    }
    
    /**
     * Build/start document
     * 
     * @return void
     */
    protected function startDocument()
    {
        
        parent::startDocument();
        
        $this->xml->startElement('ESHIPPER');
        $this->xml->startElement('LOGIN');
            $this->xml->writeElement('COMAPNY', $this->userId);
            $this->xml->writeElement('PASSWORD', $this->password);
            $this->xml->writeElement('APPID', 0);
            $this->xml->writeElement('APPVERSION', self::VERSION);
        $this->xml->endElement();
        $this->xml->startElement('CONSIGNMENTBATCH');
        
    }
    
    /**
     * Build/end document
     * 
     * @return void
     */
    protected function endDocument()
    {
        
        $this->xml->endElement();
        $this->xml->endElement();
        
        parent::endDocument();
        
    }
    
    /**
     * Build sender section
     * 
     * @return void
     */
    private function buildSenderSection()
    {
        
        $this->xml->startElement('SENDER');
            $this->xml->writeRaw( $this->sender->getAsXml() );
            $this->buildCollectionSection();
            $this->xml->writeElement('ACCOUNT', $this->account); 
        $this->xml->endElement();
        
    }
    
    /**
     * Build collection section
     * 
     * @return void
     */
    private function buildCollectionSection()
    {
        
        $this->xml->startElement('COLLECTION');
        $this->xml->startElement('COLLECTIONADDRESS');
            $this->xml->writeRaw( $this->collection->getAsXml() );
        $this->xml->endElement();
        $this->xml->endElement();
        
    }
    
    /**
     * Build consignment section
     * 
     * @return void
     */
    private function buildConsignmentSection()
    {
        
        foreach($this->consignment as $consignment) {
            
            $this->xml->startElement('CONSIGNMENT');
                $this->xml->writeRaw( $consignment->getAsXml() );
            $this->xml->endElement();
            
        }
        
    }
    
    /**
     * Build activity section
     * 
     * @return void
     */
    private function buildActivitySection()
    {
        
        /**
         * @todo Build activity section
         */
        
    }
    
}
