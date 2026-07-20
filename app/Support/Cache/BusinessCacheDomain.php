<?php

namespace App\Support\Cache;

enum BusinessCacheDomain: string
{
    case Dashboard = 'dashboard';
    case ReportsCustomerOrders = 'reports-customer-orders';
    case ReportsProduction = 'reports-production';
    case ReportsInventory = 'reports-inventory';
    case ReportsProcurement = 'reports-procurement';
    case ReportsQuality = 'reports-quality';
    case ReportsShopFloor = 'reports-shop-floor';
    case IntelligenceDashboard = 'intelligence-dashboard';
    case IntelligenceBottlenecks = 'intelligence-bottlenecks';
    case IntelligenceMaterialForecast = 'intelligence-material-forecast';
    case IntelligenceSupplierPerformance = 'intelligence-supplier-performance';
    case IntelligenceQualityTrends = 'intelligence-quality-trends';
    case IntelligenceRisks = 'intelligence-risks';
    case IntelligenceRecommendations = 'intelligence-recommendations';
    case Capacity = 'capacity';
}
