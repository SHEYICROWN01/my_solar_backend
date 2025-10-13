# How to Consume the Analytics and Reports API

## Quick Start Guide

Your Analytics API is running at `http://127.0.0.1:8001/api/reports/` with 11 powerful endpoints ready to use.

## 🌐 Frontend JavaScript/React Examples

### Basic API Client Setup

```javascript
// api/analyticsClient.js
class AnalyticsAPI {
  constructor(baseURL = 'http://127.0.0.1:8001/api/reports') {
    this.baseURL = baseURL;
  }

  async request(endpoint, options = {}) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...options.headers
        },
        ...options
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error('Analytics API Error:', error);
      throw error;
    }
  }

  // Sales trend data for line charts
  async getSalesTrend(period = '30d', startDate = null, endDate = null) {
    let query = `?period=${period}`;
    if (startDate && endDate) {
      query = `?start_date=${startDate}&end_date=${endDate}`;
    }
    return this.request(`/sales-trend${query}`);
  }

  // Category sales for pie charts
  async getSalesByCategory(period = '30d') {
    return this.request(`/sales-by-category?period=${period}`);
  }

  // Customer segments (New, Returning, VIP)
  async getCustomerSegments(period = '90d') {
    return this.request(`/customer-segments/advanced?period=${period}`);
  }

  // Top products with growth metrics
  async getTopProducts(period = '30d', limit = 10, sortBy = 'revenue') {
    return this.request(`/product-performance?period=${period}&limit=${limit}&sort_by=${sortBy}`);
  }

  // Real-time dashboard data
  async getRealTimeDashboard() {
    return this.request('/real-time-dashboard');
  }

  // Analytics overview with growth rates
  async getAnalyticsOverview(period = '30d') {
    return this.request(`/analytics-overview?period=${period}`);
  }

  // Customer lifetime value
  async getCustomerLTV(period = '1y') {
    return this.request(`/customer-ltv?period=${period}`);
  }

  // Export data
  async exportData(type, period = '30d', format = 'csv') {
    const response = await fetch(`${this.baseURL}/export?type=${type}&period=${period}&format=${format}`);
    
    if (format === 'csv') {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `analytics_${type}_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
    } else {
      return response.json();
    }
  }
}

// Initialize the API client
const analyticsAPI = new AnalyticsAPI();
```

### React Dashboard Component

```jsx
// components/AnalyticsDashboard.jsx
import React, { useState, useEffect } from 'react';
import { Line, Pie, Bar } from 'react-chartjs-2';
import { analyticsAPI } from '../api/analyticsClient';

const AnalyticsDashboard = () => {
  const [salesTrend, setSalesTrend] = useState(null);
  const [categoryData, setCategoryData] = useState(null);
  const [customerSegments, setCustomerSegments] = useState(null);
  const [realTimeData, setRealTimeData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [period, setPeriod] = useState('30d');

  useEffect(() => {
    loadDashboardData();
    
    // Set up real-time updates every 30 seconds
    const interval = setInterval(() => {
      loadRealTimeData();
    }, 30000);

    return () => clearInterval(interval);
  }, [period]);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const [salesData, categoryData, segmentData, realTimeData] = await Promise.all([
        analyticsAPI.getSalesTrend(period),
        analyticsAPI.getSalesByCategory(period),
        analyticsAPI.getCustomerSegments(period),
        analyticsAPI.getRealTimeDashboard()
      ]);

      setSalesTrend(salesData.data);
      setCategoryData(categoryData.data);
      setCustomerSegments(segmentData.data);
      setRealTimeData(realTimeData.data);
    } catch (error) {
      console.error('Failed to load dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadRealTimeData = async () => {
    try {
      const data = await analyticsAPI.getRealTimeDashboard();
      setRealTimeData(data.data);
    } catch (error) {
      console.error('Failed to update real-time data:', error);
    }
  };

  // Sales Trend Line Chart Configuration
  const salesTrendChartData = salesTrend ? {
    labels: salesTrend.sales_trend.map(item => item.period),
    datasets: [
      {
        label: 'Revenue',
        data: salesTrend.sales_trend.map(item => item.revenue),
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.1)',
        tension: 0.1,
        yAxisID: 'y'
      },
      {
        label: 'Orders',
        data: salesTrend.sales_trend.map(item => item.orders),
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.1)',
        tension: 0.1,
        yAxisID: 'y1'
      }
    ]
  } : null;

  // Category Pie Chart Configuration
  const categoryChartData = categoryData ? {
    labels: categoryData.categories.map(cat => cat.category_name),
    datasets: [{
      data: categoryData.categories.map(cat => cat.revenue),
      backgroundColor: [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
        '#9966FF', '#FF9F40', '#FF6384'
      ],
      borderWidth: 2
    }]
  } : null;

  // Customer Segments Bar Chart Configuration
  const customerSegmentsChartData = customerSegments ? {
    labels: ['New', 'Returning', 'VIP'],
    datasets: [{
      label: 'Customers',
      data: [
        customerSegments.segments.new,
        customerSegments.segments.returning,
        customerSegments.segments.vip
      ],
      backgroundColor: ['#36A2EB', '#FFCE56', '#4BC0C0'],
      borderColor: ['#2E8B99', '#E6B800', '#3A9999'],
      borderWidth: 1
    }]
  } : null;

  if (loading) {
    return <div className="loading">Loading analytics...</div>;
  }

  return (
    <div className="analytics-dashboard">
      {/* Header with Period Selector */}
      <div className="dashboard-header">
        <h1>Analytics Dashboard</h1>
        <select value={period} onChange={(e) => setPeriod(e.target.value)}>
          <option value="7d">Last 7 Days</option>
          <option value="30d">Last 30 Days</option>
          <option value="90d">Last 90 Days</option>
          <option value="1y">Last Year</option>
        </select>
      </div>

      {/* Real-time KPI Cards */}
      {realTimeData && (
        <div className="kpi-cards">
          <div className="kpi-card">
            <h3>Today's Revenue</h3>
            <div className="kpi-value">${realTimeData.today.metrics.revenue.toLocaleString()}</div>
            <div className={`kpi-growth ${realTimeData.today.vs_yesterday.revenue_growth >= 0 ? 'positive' : 'negative'}`}>
              {realTimeData.today.vs_yesterday.revenue_growth >= 0 ? '↗' : '↘'} 
              {Math.abs(realTimeData.today.vs_yesterday.revenue_growth)}%
            </div>
          </div>
          
          <div className="kpi-card">
            <h3>Today's Orders</h3>
            <div className="kpi-value">{realTimeData.today.metrics.orders}</div>
            <div className={`kpi-growth ${realTimeData.today.vs_yesterday.orders_growth >= 0 ? 'positive' : 'negative'}`}>
              {realTimeData.today.vs_yesterday.orders_growth >= 0 ? '↗' : '↘'} 
              {Math.abs(realTimeData.today.vs_yesterday.orders_growth)}%
            </div>
          </div>

          <div className="kpi-card">
            <h3>This Week's Revenue</h3>
            <div className="kpi-value">${realTimeData.this_week.metrics.revenue.toLocaleString()}</div>
            <div className={`kpi-growth ${realTimeData.this_week.vs_last_week.revenue_growth >= 0 ? 'positive' : 'negative'}`}>
              {realTimeData.this_week.vs_last_week.revenue_growth >= 0 ? '↗' : '↘'} 
              {Math.abs(realTimeData.this_week.vs_last_week.revenue_growth)}%
            </div>
          </div>
        </div>
      )}

      {/* Charts Grid */}
      <div className="charts-grid">
        {/* Sales Trend Line Chart */}
        <div className="chart-container">
          <h3>Sales Trend</h3>
          {salesTrendChartData && (
            <Line 
              data={salesTrendChartData}
              options={{
                responsive: true,
                scales: {
                  y: { type: 'linear', display: true, position: 'left' },
                  y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false } }
                }
              }}
            />
          )}
        </div>

        {/* Category Pie Chart */}
        <div className="chart-container">
          <h3>Sales by Category</h3>
          {categoryChartData && (
            <Pie 
              data={categoryChartData}
              options={{
                responsive: true,
                plugins: {
                  legend: { position: 'bottom' },
                  tooltip: {
                    callbacks: {
                      label: (context) => {
                        const category = categoryData.categories[context.dataIndex];
                        return `${category.category_name}: $${category.revenue.toLocaleString()} (${category.percentage}%)`;
                      }
                    }
                  }
                }
              }}
            />
          )}
        </div>

        {/* Customer Segments Bar Chart */}
        <div className="chart-container">
          <h3>Customer Segments</h3>
          {customerSegmentsChartData && (
            <Bar 
              data={customerSegmentsChartData}
              options={{
                responsive: true,
                plugins: {
                  tooltip: {
                    callbacks: {
                      label: (context) => {
                        const segment = ['new', 'returning', 'vip'][context.dataIndex];
                        const percentage = customerSegments.percentages[segment];
                        return `${context.label}: ${context.raw} customers (${percentage}%)`;
                      }
                    }
                  }
                }
              }}
            />
          )}
        </div>
      </div>

      {/* Export Buttons */}
      <div className="export-section">
        <h3>Export Data</h3>
        <div className="export-buttons">
          <button onClick={() => analyticsAPI.exportData('sales_trend', period, 'csv')}>
            Export Sales Trend (CSV)
          </button>
          <button onClick={() => analyticsAPI.exportData('category_sales', period, 'csv')}>
            Export Category Sales (CSV)
          </button>
          <button onClick={() => analyticsAPI.exportData('top_products', period, 'csv')}>
            Export Top Products (CSV)
          </button>
        </div>
      </div>
    </div>
  );
};

export default AnalyticsDashboard;
```

### CSS Styles for Dashboard

```css
/* styles/dashboard.css */
.analytics-dashboard {
  padding: 20px;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.dashboard-header h1 {
  margin: 0;
  color: #333;
}

.dashboard-header select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.kpi-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.kpi-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  text-align: center;
}

.kpi-card h3 {
  margin: 0 0 10px 0;
  color: #666;
  font-size: 14px;
  font-weight: 500;
}

.kpi-value {
  font-size: 28px;
  font-weight: bold;
  color: #333;
  margin-bottom: 10px;
}

.kpi-growth {
  font-size: 14px;
  font-weight: 500;
}

.kpi-growth.positive {
  color: #10b981;
}

.kpi-growth.negative {
  color: #ef4444;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.chart-container {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chart-container h3 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 18px;
}

.export-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.export-section h3 {
  margin: 0 0 15px 0;
  color: #333;
}

.export-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.export-buttons button {
  padding: 10px 20px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.export-buttons button:hover {
  background: #2563eb;
}

.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 200px;
  font-size: 18px;
  color: #666;
}
```

## 📱 Mobile-First React Native Example

```javascript
// components/MobileAnalytics.jsx
import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, RefreshControl, StyleSheet } from 'react-native';
import { Picker } from '@react-native-picker/picker';

const MobileAnalytics = () => {
  const [data, setData] = useState(null);
  const [period, setPeriod] = useState('30d');
  const [refreshing, setRefreshing] = useState(false);

  const loadData = async () => {
    try {
      const response = await fetch(`http://127.0.0.1:8001/api/reports/analytics-overview?period=${period}`);
      const result = await response.json();
      setData(result.data);
    } catch (error) {
      console.error('Error loading analytics:', error);
    }
  };

  useEffect(() => {
    loadData();
  }, [period]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadData();
    setRefreshing(false);
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <View style={styles.header}>
        <Text style={styles.title}>Analytics</Text>
        <Picker
          selectedValue={period}
          onValueChange={setPeriod}
          style={styles.picker}
        >
          <Picker.Item label="7 Days" value="7d" />
          <Picker.Item label="30 Days" value="30d" />
          <Picker.Item label="90 Days" value="90d" />
          <Picker.Item label="1 Year" value="1y" />
        </Picker>
      </View>

      {data && (
        <View style={styles.metricsContainer}>
          <View style={styles.metricCard}>
            <Text style={styles.metricLabel}>Revenue</Text>
            <Text style={styles.metricValue}>${data.current.revenue.toLocaleString()}</Text>
            <Text style={[styles.growth, data.growth.revenue >= 0 ? styles.positive : styles.negative]}>
              {data.growth.revenue >= 0 ? '↗' : '↘'} {Math.abs(data.growth.revenue)}%
            </Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricLabel}>Orders</Text>
            <Text style={styles.metricValue}>{data.current.orders}</Text>
            <Text style={[styles.growth, data.growth.orders >= 0 ? styles.positive : styles.negative]}>
              {data.growth.orders >= 0 ? '↗' : '↘'} {Math.abs(data.growth.orders)}%
            </Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricLabel}>Customers</Text>
            <Text style={styles.metricValue}>{data.current.customers}</Text>
            <Text style={[styles.growth, data.growth.customers >= 0 ? styles.positive : styles.negative]}>
              {data.growth.customers >= 0 ? '↗' : '↘'} {Math.abs(data.growth.customers)}%
            </Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricLabel}>Avg Order Value</Text>
            <Text style={styles.metricValue}>${data.current.avg_order_value.toFixed(2)}</Text>
            <Text style={[styles.growth, data.growth.avg_order_value >= 0 ? styles.positive : styles.negative]}>
              {data.growth.avg_order_value >= 0 ? '↗' : '↘'} {Math.abs(data.growth.avg_order_value)}%
            </Text>
          </View>
        </View>
      )}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f5f5f5' },
  header: { padding: 20, backgroundColor: 'white', flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  title: { fontSize: 24, fontWeight: 'bold', color: '#333' },
  picker: { width: 120 },
  metricsContainer: { padding: 20, flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between' },
  metricCard: { width: '48%', backgroundColor: 'white', padding: 15, borderRadius: 8, marginBottom: 15, alignItems: 'center' },
  metricLabel: { fontSize: 14, color: '#666', marginBottom: 5 },
  metricValue: { fontSize: 20, fontWeight: 'bold', color: '#333', marginBottom: 5 },
  growth: { fontSize: 12, fontWeight: '500' },
  positive: { color: '#10b981' },
  negative: { color: '#ef4444' }
});

export default MobileAnalytics;
```

## 🐍 Python/Django Backend Integration

```python
# analytics/client.py
import requests
from typing import Dict, List, Optional
import pandas as pd

class AnalyticsClient:
    def __init__(self, base_url: str = "http://127.0.0.1:8001/api/reports"):
        self.base_url = base_url
    
    def _make_request(self, endpoint: str, params: Dict = None) -> Dict:
        """Make HTTP request to analytics API"""
        try:
            response = requests.get(f"{self.base_url}{endpoint}", params=params)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            raise Exception(f"Analytics API request failed: {e}")
    
    def get_sales_trend(self, period: str = "30d", start_date: str = None, end_date: str = None) -> Dict:
        """Get sales trend data"""
        params = {"period": period}
        if start_date and end_date:
            params = {"start_date": start_date, "end_date": end_date}
        return self._make_request("/sales-trend", params)
    
    def get_category_sales(self, period: str = "30d") -> Dict:
        """Get sales by category"""
        return self._make_request("/sales-by-category", {"period": period})
    
    def get_customer_segments(self, period: str = "90d") -> Dict:
        """Get customer segments (New, Returning, VIP)"""
        return self._make_request("/customer-segments/advanced", {"period": period})
    
    def get_top_products(self, period: str = "30d", limit: int = 10, sort_by: str = "revenue") -> Dict:
        """Get top performing products"""
        params = {"period": period, "limit": limit, "sort_by": sort_by}
        return self._make_request("/product-performance", params)
    
    def get_real_time_dashboard(self) -> Dict:
        """Get real-time dashboard metrics"""
        return self._make_request("/real-time-dashboard")
    
    def export_to_dataframe(self, data_type: str, period: str = "30d") -> pd.DataFrame:
        """Export data to pandas DataFrame for analysis"""
        if data_type == "sales_trend":
            data = self.get_sales_trend(period)
            return pd.DataFrame(data["data"]["sales_trend"])
        elif data_type == "category_sales":
            data = self.get_category_sales(period)
            return pd.DataFrame(data["data"]["categories"])
        elif data_type == "top_products":
            data = self.get_top_products(period)
            return pd.DataFrame(data["data"]["products"])
        else:
            raise ValueError("Invalid data_type. Use: sales_trend, category_sales, or top_products")

# Django views example
# views.py
from django.http import JsonResponse
from django.shortcuts import render
from .analytics.client import AnalyticsClient

def dashboard_view(request):
    """Django view for analytics dashboard"""
    analytics = AnalyticsClient()
    
    try:
        # Get dashboard data
        sales_trend = analytics.get_sales_trend("30d")
        category_sales = analytics.get_category_sales("30d")
        customer_segments = analytics.get_customer_segments("90d")
        real_time = analytics.get_real_time_dashboard()
        
        context = {
            'sales_trend': sales_trend['data'],
            'category_sales': category_sales['data'],
            'customer_segments': customer_segments['data'],
            'real_time': real_time['data']
        }
        
        return render(request, 'dashboard.html', context)
    
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=500)

def api_analytics(request):
    """API endpoint for analytics data"""
    analytics = AnalyticsClient()
    period = request.GET.get('period', '30d')
    
    try:
        data = {
            'overview': analytics.get_sales_trend(period)['data'],
            'categories': analytics.get_category_sales(period)['data'],
            'segments': analytics.get_customer_segments(period)['data']
        }
        return JsonResponse(data)
    
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=500)
```

## 📊 Data Science/Jupyter Notebook Integration

```python
# analytics_notebook.py
import requests
import pandas as pd
import matplotlib.pyplot as plt
import seaborn as sns
from datetime import datetime, timedelta

# Set up the analytics client
class NotebookAnalytics:
    def __init__(self):
        self.base_url = "http://127.0.0.1:8001/api/reports"
    
    def get_data(self, endpoint, params=None):
        response = requests.get(f"{self.base_url}{endpoint}", params=params)
        return response.json()['data']
    
    def analyze_sales_trend(self, period="90d"):
        """Comprehensive sales trend analysis"""
        data = self.get_data("/sales-trend", {"period": period})
        df = pd.DataFrame(data['sales_trend'])
        df['period'] = pd.to_datetime(df['period'])
        
        # Create visualizations
        fig, ((ax1, ax2), (ax3, ax4)) = plt.subplots(2, 2, figsize=(15, 10))
        
        # Revenue trend
        ax1.plot(df['period'], df['revenue'], marker='o')
        ax1.set_title('Revenue Trend')
        ax1.set_ylabel('Revenue ($)')
        ax1.tick_params(axis='x', rotation=45)
        
        # Orders trend
        ax2.plot(df['period'], df['orders'], marker='o', color='orange')
        ax2.set_title('Orders Trend')
        ax2.set_ylabel('Number of Orders')
        ax2.tick_params(axis='x', rotation=45)
        
        # Customers trend
        ax3.plot(df['period'], df['customers'], marker='o', color='green')
        ax3.set_title('Unique Customers Trend')
        ax3.set_ylabel('Number of Customers')
        ax3.tick_params(axis='x', rotation=45)
        
        # Average order value
        df['avg_order_value'] = df['revenue'] / df['orders']
        ax4.plot(df['period'], df['avg_order_value'], marker='o', color='red')
        ax4.set_title('Average Order Value Trend')
        ax4.set_ylabel('AOV ($)')
        ax4.tick_params(axis='x', rotation=45)
        
        plt.tight_layout()
        plt.show()
        
        return df
    
    def analyze_customer_segments(self):
        """Customer segmentation analysis"""
        data = self.get_data("/customer-segments/advanced", {"period": "1y"})
        
        # Create pie chart
        segments = data['segments']
        labels = list(segments.keys())
        sizes = list(segments.values())
        colors = ['#ff9999', '#66b3ff', '#99ff99']
        
        plt.figure(figsize=(10, 8))
        plt.pie(sizes, labels=labels, colors=colors, autopct='%1.1f%%', startangle=90)
        plt.title('Customer Segments Distribution')
        plt.axis('equal')
        plt.show()
        
        # Print detailed analysis
        print("Customer Segments Analysis:")
        print(f"Total Customers: {data['total_customers']}")
        for segment, count in segments.items():
            percentage = data['percentages'][segment]
            print(f"{segment.title()}: {count} customers ({percentage}%)")
    
    def product_performance_analysis(self):
        """Analyze top product performance with growth metrics"""
        data = self.get_data("/product-performance", {"period": "30d", "limit": 20})
        df = pd.DataFrame(data['products'])
        
        # Create subplots
        fig, ((ax1, ax2), (ax3, ax4)) = plt.subplots(2, 2, figsize=(16, 12))
        
        # Top 10 by revenue
        top_revenue = df.head(10)
        ax1.barh(top_revenue['name'], top_revenue['revenue'])
        ax1.set_title('Top 10 Products by Revenue')
        ax1.set_xlabel('Revenue ($)')
        
        # Revenue growth
        ax2.scatter(df['revenue'], df['revenue_growth'], alpha=0.6)
        ax2.set_xlabel('Revenue ($)')
        ax2.set_ylabel('Revenue Growth (%)')
        ax2.set_title('Revenue vs Growth Rate')
        ax2.axhline(y=0, color='red', linestyle='--', alpha=0.5)
        
        # Trending products
        trending = df[df['is_trending'] == True]
        ax3.bar(range(len(trending)), trending['revenue_growth'])
        ax3.set_title('Trending Products Growth Rate')
        ax3.set_ylabel('Growth Rate (%)')
        ax3.set_xticks(range(len(trending)))
        ax3.set_xticklabels(trending['name'], rotation=45, ha='right')
        
        # Category performance
        category_revenue = df.groupby('category_name')['revenue'].sum().sort_values(ascending=False)
        ax4.pie(category_revenue.values, labels=category_revenue.index, autopct='%1.1f%%')
        ax4.set_title('Revenue by Category')
        
        plt.tight_layout()
        plt.show()
        
        return df

# Usage in Jupyter Notebook
analytics = NotebookAnalytics()

# Analyze sales trends
sales_df = analytics.analyze_sales_trend("90d")

# Customer segmentation
analytics.analyze_customer_segments()

# Product performance
products_df = analytics.product_performance_analysis()

# Export data for further analysis
sales_df.to_csv('sales_trend_analysis.csv', index=False)
products_df.to_csv('product_performance_analysis.csv', index=False)
```

## 🔄 Real-time Updates with WebSockets (Optional Enhancement)

```javascript
// websockets/analyticsSocket.js
class AnalyticsWebSocket {
  constructor(wsUrl = 'ws://127.0.0.1:8001/ws/analytics') {
    this.wsUrl = wsUrl;
    this.listeners = {};
    this.connect();
  }

  connect() {
    this.ws = new WebSocket(this.wsUrl);
    
    this.ws.onopen = () => {
      console.log('Analytics WebSocket connected');
      this.requestRealTimeUpdates();
    };

    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      this.handleMessage(data);
    };

    this.ws.onclose = () => {
      console.log('Analytics WebSocket disconnected, reconnecting...');
      setTimeout(() => this.connect(), 5000);
    };
  }

  requestRealTimeUpdates() {
    this.ws.send(JSON.stringify({
      type: 'subscribe',
      events: ['order_created', 'payment_completed', 'inventory_update']
    }));
  }

  handleMessage(data) {
    if (this.listeners[data.type]) {
      this.listeners[data.type].forEach(callback => callback(data.payload));
    }
  }

  on(event, callback) {
    if (!this.listeners[event]) {
      this.listeners[event] = [];
    }
    this.listeners[event].push(callback);
  }
}

// Usage
const analyticsWS = new AnalyticsWebSocket();

analyticsWS.on('new_order', (orderData) => {
  // Update dashboard with new order
  updateDashboardMetrics();
});

analyticsWS.on('payment_completed', (paymentData) => {
  // Refresh revenue metrics
  refreshRevenueData();
});
```

## 📱 Quick Testing Commands

Test your API endpoints directly:

```bash
# Test sales trend
curl "http://127.0.0.1:8001/api/reports/sales-trend?period=30d" | jq '.'

# Test category sales
curl "http://127.0.0.1:8001/api/reports/sales-by-category?period=30d" | jq '.'

# Test customer segments
curl "http://127.0.0.1:8001/api/reports/customer-segments/advanced?period=90d" | jq '.'

# Test real-time dashboard
curl "http://127.0.0.1:8001/api/reports/real-time-dashboard" | jq '.'

# Export CSV
curl "http://127.0.0.1:8001/api/reports/export?type=sales_trend&period=30d&format=csv" > sales_data.csv
```

## 🚀 Next Steps

1. **Install Chart.js or D3.js** for advanced visualizations
2. **Set up authentication** if needed for production
3. **Implement caching** for better performance
4. **Add real-time updates** using WebSockets
5. **Create scheduled reports** via email
6. **Integrate with business intelligence tools** like Tableau or Power BI

Your Analytics API is ready to power sophisticated dashboards and data-driven business decisions!