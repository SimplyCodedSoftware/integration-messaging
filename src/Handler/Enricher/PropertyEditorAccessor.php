<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class PayloadPropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PropertyEditorAccessor
{
    /**
     * @var string
     */
    private $mappingExpression;
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var ReferenceSearchService
     */
    private $referenceSearchService;

    /**
     * DataSetter constructor.
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param string $mappingExpression
     */
    private function __construct(ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, string $mappingExpression)
    {
        $this->mappingExpression = $mappingExpression;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->referenceSearchService = $referenceSearchService;
    }

    /**
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param string $mappingExpression
     * @return PropertyEditorAccessor
     */
    public static function create(ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, string $mappingExpression): self
    {
        return new self($expressionEvaluationService, $referenceSearchService, $mappingExpression);
    }

    /**
     * @param PropertyPath $propertyNamePath
     * @param mixed $dataToEnrich
     * @param mixed $dataToEnrichWith
     *
     * @param Message $requestMessage
     * @param null|Message $replyMessage
     * @return mixed enriched data
     * @throws EnrichException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function enrichDataWith(PropertyPath $propertyNamePath, $dataToEnrich, $dataToEnrichWith, Message $requestMessage, ?Message $replyMessage)
    {
        $propertyName = $propertyNamePath->getPath();

        if (preg_match("#^(\[\*\])#", $propertyName)) {
            $propertyToBeChanged = $this->cutOutCurrentAccessPropertyName($propertyNamePath, "[*]");
            $newPayload = $dataToEnrich;
            foreach ($dataToEnrich as $propertyKey => $context) {
                $enriched = false;
                foreach ($dataToEnrichWith as $replyElement) {
                    if ($this->canBeMapped($context, $replyElement, $requestMessage, $replyMessage)) {
                        $newPayload[$propertyKey] = $this->enrichDataWith($propertyToBeChanged, $newPayload[$propertyKey], $replyElement, $requestMessage, $replyMessage);
                        $enriched = true;
                        break;
                    };
                }

                if (!$enriched) {
                    throw InvalidArgumentException::createWithFailedMessage("Can't enrich message {$requestMessage}. Can't find mapped data for {$propertyKey} in {$replyMessage}", $requestMessage);
                }
            }

            return $newPayload;
        }

        /** [0][data][worker] */
        preg_match("#^\[([a-zA-Z0-9]*)\]#", $propertyNamePath->getPath(), $startingWithPath);
        if ($this->hasAnyMatches($startingWithPath)) {
            $propertyName = $startingWithPath[1];
            $accessPropertyName = $startingWithPath[0];
            if ($accessPropertyName !== $propertyNamePath->getPath()) {
                $dataToEnrichWith = $this->enrichDataWith($this->cutOutCurrentAccessPropertyName($propertyNamePath, $accessPropertyName), $dataToEnrich[$propertyName], $dataToEnrichWith, $requestMessage, $replyMessage);
            }
        }else {
            /** worker[name] */
            preg_match('#\b([^\[\]]*)\[[a-zA-Z0-9]*\]#', $propertyNamePath->getPath(), $startingWithPropertyName);

            if ($this->hasAnyMatches($startingWithPropertyName)) {
                $propertyName = $startingWithPropertyName[1];

                if ($propertyName !== $propertyNamePath->getPath()) {
                    $dataToEnrichWith = $this->enrichDataWith($this->cutOutCurrentAccessPropertyName($propertyNamePath, $propertyName), $dataToEnrich[$propertyName], $dataToEnrichWith, $requestMessage, $replyMessage);
                }
            }
        }

        if (is_array($dataToEnrich)) {
            $newPayload = $dataToEnrich;
            $newPayload[$propertyName] = $dataToEnrichWith;

            return $newPayload;
        }

        if (is_object($dataToEnrich)) {
            $setterMethod = "set" . ucfirst($propertyName);

            if (method_exists($dataToEnrich, $setterMethod)) {
                $dataToEnrich->{$setterMethod}($dataToEnrichWith);

                return $dataToEnrich;
            }

            $objectReflection = new \ReflectionClass($dataToEnrich);

            if (!$objectReflection->hasProperty($propertyName)) {
                throw EnrichException::create("Object for enriching has no property named {$propertyName}");
            }

            $classProperty = $objectReflection->getProperty($propertyName);

            $classProperty->setAccessible(true);
            $classProperty->setValue($dataToEnrich, $dataToEnrichWith);

            return $dataToEnrich;
        }
    }

    /**
     * @param $matches
     *
     * @return bool
     */
    private function hasAnyMatches($matches): bool
    {
        return !empty($matches);
    }

    /**
     * @param PropertyPath $propertyName
     * @param string $accessPropertyName
     *
     * @return PropertyPath
     */
    private function cutOutCurrentAccessPropertyName(PropertyPath $propertyName, string $accessPropertyName) : PropertyPath
    {
        return PropertyPath::createWith(substr($propertyName->getPath(), strlen($accessPropertyName), strlen($propertyName->getPath())));
    }

    /**
     * @param $context
     * @param $replyElement
     * @param Message $requestMessage
     * @param null|Message $replyMessage
     * @return bool
     */
    private function canBeMapped($context, $replyElement, Message $requestMessage, ?Message $replyMessage): bool
    {
        return $this->expressionEvaluationService->evaluate(
            $this->mappingExpression,
            [
                "payload" => $replyMessage ? $replyMessage->getPayload() : null,
                "headers" => $replyMessage ? $replyMessage->getHeaders()->headers() : null,
                "request" => [
                    "payload" => $requestMessage->getPayload(),
                    "headers" => $requestMessage->getHeaders()
                ],
                "requestContext" => $context,
                "replyContext" => $replyElement,
                "referenceService" => $this->referenceSearchService
            ]
        );
    }
}