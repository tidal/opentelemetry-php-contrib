<?php

declare(strict_types=1);

namespace OpenTelemetry\Instrumentation\Symfony\OtelSdkBundle\Resources;

use OpenTelemetry\Instrumentation\Symfony\OtelSdkBundle\DependencyInjection\Parameters;
use OpenTelemetry\Instrumentation\Symfony\OtelSdkBundle\DependencyInjection\Ids;
use OpenTelemetry\Instrumentation\Symfony\OtelSdkBundle\Util\ConfigHelper;
use OpenTelemetry\Instrumentation\Symfony\OtelSdkBundle\Util\ServiceHelper;
use OpenTelemetry\Instrumentation\Symfony\OtelSdkBundle\Util\ServicesConfiguratorHelper;
use OpenTelemetry\SDK\Resource;
use OpenTelemetry\SDK\Trace;
use OpenTelemetry\SDK\Trace\Sampler;
use OpenTelemetry\SDK\Trace\SpanProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    /**
     * @codeCoverageIgnoreStart
     */
    $helper = new ServicesConfiguratorHelper($containerConfigurator->services());

    ///////////////////////
    // Parameters
    ///////////////////////
    foreach (Parameters::DEFAULTS as $key => $value) {
        $containerConfigurator->parameters()->set($key, $value);
    }

    ///////////////////////
    // Services
    ///////////////////////

    // UTIL

    $helper->setService(Trace\SystemClock::class);

    $helper->setService(Trace\RandomIdGenerator::class);

    // RESOURCE

    $helper->setService(Trace\AttributeLimits::class);

    $helper->setService(Trace\Attributes::class)
        ->args([
            ConfigHelper::wrapParameter(Parameters::RESOURCE_ATTRIBUTES),
            ConfigHelper::createReferenceFromClass(Trace\AttributeLimits::class),
        ]);

    $helper->setService(Resource\ResourceInfo::class)
        ->factory([Resource\ResourceInfo::class , 'create'])
        ->args([
            ConfigHelper::createReferenceFromClass(Trace\Attributes::class),
        ]);

    // SAMPLER

    $helper->setService(Sampler\AlwaysOnSampler::class);

    $helper->setService(Sampler\AlwaysOffSampler::class);

    $helper->setService(Sampler\TraceIdRatioBasedSampler::class)
        ->args([ConfigHelper::wrapParameter(Parameters::SAMPLER_TRACE_ID_RATIO_BASED_DEFAULT_RATIO)])
        // alias with sample ratio suffix
        ->alias(
            sprintf(
                '%s.%s',
                ServiceHelper::classToId(Sampler\TraceIdRatioBasedSampler::class),
                ServiceHelper::floatToString((float) Parameters::DEFAULT_SAMPLER_TRACE_ID_RATIO_BASED_DEFAULT_RATIO)
            ),
            ServiceHelper::classToId(Sampler\TraceIdRatioBasedSampler::class)
        );

    $helper->setService(Sampler\ParentBased::class)
        ->args([ConfigHelper::createReferenceFromClass(Sampler\AlwaysOnSampler::class)]);

    // SPAN

    $helper->setService(Trace\SpanLimitsBuilder::class);

    $helper->setService(Trace\SpanLimits::class)
        ->factory([
            ConfigHelper::createReferenceFromClass(Trace\SpanLimitsBuilder::class),
            'build',
        ]);

    $helper->setService(SpanProcessor\SimpleSpanProcessor::class);

    $helper->setService(SpanProcessor\MultiSpanProcessor::class);

    $helper->setService(SpanProcessor\NoopSpanProcessor::class);

    $helper->setService(SpanProcessor\BatchSpanProcessor::class)
        ->args([
            null,
            ConfigHelper::createReferenceFromClass(Trace\SystemClock::class),
        ]);
    $helper->setAlias(
        Ids::SPAN_PROCESSOR_DEFAULT,
        ServiceHelper::classToId(SpanProcessor\BatchSpanProcessor::class)
    );

    // TRACER

    $helper->setService(Trace\TracerProvider::class)
        ->args([
            ConfigHelper::createReferenceFromClass(SpanProcessor\NoopSpanProcessor::class),
            ConfigHelper::createReferenceFromClass(Sampler\AlwaysOnSampler::class),
            ConfigHelper::createReferenceFromClass(Resource\ResourceInfo::class),
            ConfigHelper::createReferenceFromClass(Trace\SpanLimits::class),
            ConfigHelper::createReferenceFromClass(Trace\RandomIdGenerator::class),
        ]);
    /**
     * @codeCoverageIgnoreEnd
     */
};

