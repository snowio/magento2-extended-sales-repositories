<?php
namespace SnowIO\ExtendedSalesRepositories\Exception;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;

/**
 * Class SnowCreditMemoException
 *
 * @package SnowIO\ExtendedSalesRepositories\Exception
 */
class SnowCreditMemoException extends Exception
{
    /**
     * HTTP code precondition failed
     */
    const HTTP_PRECONDITION_FAILED = 412;

    /**
     * SnowCreditMemoException constructor.
     *
     * Magento does not support HTTP response code `412` by default, extend the web API exception and set manually
     *
     * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
     * @param \Magento\Framework\Phrase $phrase
     * @param int                       $code
     * @param int                       $httpCode
     * @param array                     $details
     * @param string                    $name
     * @param null                      $errors
     * @param null                      $stackTrace
     */
    public function __construct(
        Phrase $phrase,
        $code = 0,
        $httpCode = self::HTTP_PRECONDITION_FAILED,
        array $details = [],
        $name = '',
        $errors = null,
        $stackTrace = null
    ) {
        parent::__construct($phrase, $code, $httpCode, $details, $name, $errors, $stackTrace);
    }
}
