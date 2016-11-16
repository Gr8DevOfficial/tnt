<?php

/**
 * Tracking Service
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 */

namespace thm\tnt_ec\Service\TrackingService;

use thm\tnt_ec\Service\AbstractService;
use thm\tnt_ec\Service\TrackingService\libs\TrackingResponse;
use thm\tnt_ec\Service\TrackingService\libs\LevelOfDetails;
use thm\tnt_ec\TNTException;

class TrackingService extends AbstractService {
    
    /* Market types */
    const M_ITL = 'INTERNATIONAL';
    const M_DST = 'DOMESTIC';
    
    /**
     * Search date from
     * 
     * @var string
     */
    private $dateFrom = null;
    
    /**
     * Search date to
     * 
     * @var string
     */
    private $dateTo = null;
    
    /**
     * Number of days to search from days
     * 
     * @var int
     */
    private $days = 0;
    
    /**
     * Market type
     * 
     * @var string
     */
    private $marketType = TrackingService::M_DST;
    
    /**
     * Locale - translate.
     * English US set as default.
     * 
     * @var string
     */
    private $locale = 'en_US';
    
    /**
     * @var LevelOfDetails
     */
    private $lod;
    
    /**
     * Search by consignment numbers (TNT reference)
     * 
     * @param array $consignments
     * @return TrackingResponse
     */
    public function searchByConsignment(array $consignments)
    {
        
        $this->xml->flush();
        
        $this->startDocument();
            
            foreach($consignments as $consignment) {

                $this->xml->writeElement('ConsignmentNumber', $consignment);

            }
                
        $this->endDocument();
            
        return new TrackingResponse( $this->sendRequest(), $this->getXmlContent() );
        
    }
    
    /**
     * Search by customer references (your reference)
     * 
     * @param array $references
     * @return TrackingResponse
     */
    public function searchByCustomerReference(array $references)
    {
        
        $this->xml->flush();
        
        $this->startDocument();
            
            foreach($references as $reference) {

                $this->xml->writeElement('CustomerReference', $reference);

            }
                
        $this->endDocument();
            
        return new TrackingResponse( $this->sendRequest(), $this->getXmlContent() );
        
    }

    /**
     * Search by date period
     * 
     * @param string $dateFrom Format: YYYYMMDD
     * @param string $dateTo [optional] Format YYYYMMDD
     * @param int $days [optional] Number of days followind $dateFrom. 
     * If $dateTo is set, then $days will be ignored by TNT.
     * 
     * @return TrackingResponse
     */
    public function searchByDate($dateFrom, $dateTo = null, $days = 3)
    {
        
        $this->xml->flush();
        
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
        $this->days     = $days;
        
        $this->startDocument();
            
        $this->setSearchByDateCriteria();
                
        $this->endDocument();
            
        return new TrackingResponse( $this->sendRequest(), $this->getXmlContent() );
        
    }
    
    /**
     * Set market type DOMESTIC
     * 
     * @return TrackingService
     */
    public function setMarketTypeDomestic()
    {
        
        $this->marketType = TrackingService::M_DST;
        return $this;
        
    }

    /**
     * Set market type INTERNATIONAL
     * 
     * @return TrackingService
     */
    public function setMarketTypeInternational()
    {
        
        $this->marketType = TrackingService::M_ITL;
        return $this;
        
    }
    
    /**
     * Set locale - translate attempt.
     * Will attempt to transalte status description in to relevant local language.
     * 
     * @param string $countryCode If not specified, English is set to default.
     * @return TrackingService
     */
    public function setLocale($countryCode) 
    {
        
        $this->locale = $countryCode;
        return $this;
        
    }
    
    /**
     * Set level of details returned
     * 
     * @return LevelOfDetails
     */
    public function setLevelOfDetails()
    {
        
        if($this->lod instanceof LevelOfDetails) {
            
            return $this->lod;
            
        } else {
            
            $this->lod = new LevelOfDetails( $this );
            
            return $this->lod;
            
        }
        
    }

    /**
     * Start document
     * 
     * @return void
     */
    protected function startDocument()
    {
        
        parent::startDocument();
        
        $this->xml->startElement("TrackRequest");
        $this->xml->writeAttribute('locale', $this->locale);
        $this->xml->writeAttribute('version', AbstractService::VERSION);
        $this->xml->startElement("SearchCriteria");
        $this->setMarketTypeAttributes();
            
    }
    
    /**
     * End document
     * 
     * @return void
     */
    protected function endDocument()
    {
        
        $this->xml->endElement();
        $this->xml->writeRaw( $this->setLevelOfDetails()->getXml() );
        $this->xml->endElement();
        
        parent::endDocument();
        
    }
    
    /**
     * Set search by account criteria
     * 
     * @return void
     * @throws TNTException
     */
    private function setSearchByDateCriteria()
    {
        
        if(empty($this->dateFrom) === false) {
            
            $this->xml->startElement('Account');
                $this->xml->writeElement('Number', $this->account);
                $this->xml->writeElement('CountryCode', $this->accountCountryCode);
            $this->xml->endElement();
            
            $this->xml->startElement('Period');
                $this->xml->writeElement('DateFrom', $this->dateFrom);
                $this->xml->writeElement('DateTo', $this->dateTo);
                $this->xml->writeElement('NumberOfDays', $this->days);
            $this->xml->endElement();
            
        }
        
    }
    
    /**
     * Set market type attributes for search criteria
     * 
     * @return void
     */
    private function setMarketTypeAttributes()
    {
        
        if(empty($this->marketType) === false) {
            
            $this->xml->writeAttribute('marketType', $this->marketType);
            $this->xml->writeAttribute('originCountry', $this->originCountryCode);
                        
        }
        
    }
    
}
