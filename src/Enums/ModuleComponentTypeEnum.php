<?php

namespace Laraneat\Modules\Enums;

enum ModuleComponentTypeEnum: string
{
    case Action = 'action';
    case ApiController = 'api-controller';
    case ApiQueryWizard = 'api-query-wizard';
    case ApiRequest = 'api-request';
    case ApiResource = 'api-resource';
    case ApiRoute = 'api-route';
    case ApiTest = 'api-test';
    case CliCommand = 'cli-command';
    case CliTest = 'cli-test';
    case Dto = 'dto';
    case Event = 'event';
    case Exception = 'exception';
    case Factory = 'factory';
    case FeatureTest = 'feature-test';
    case Job = 'job';
    case Lang = 'lang';
    case Listener = 'listener';
    case Mail = 'mail';
    case Middleware = 'middleware';
    case Migration = 'migration';
    case Model = 'model';
    case Notification = 'notification';
    case Observer = 'observer';
    case Policy = 'policy';
    case Provider = 'provider';
    case Rule = 'rule';
    case Seeder = 'seeder';
    case WebController = 'web-controller';
    case WebRequest = 'web-request';
    case WebRoute = 'web-route';
    case WebTest = 'web-test';
    case View = 'view';
    case UnitTest = 'unit-test';
}
