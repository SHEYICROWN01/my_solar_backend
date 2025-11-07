# Frontend Integration Guide - Solar E-commerce Backend

## 🌐 Production API Base URL

Replace all localhost API URLs with:
```
https://web-production-e65f7.up.railway.app/api
```

---

## 📋 API Endpoints Overview

### **Base Configuration**
- **Production API URL**: `https://web-production-e65f7.up.railway.app/api`
- **Local Development URL**: `http://localhost:8000/api`
- **Authentication**: Laravel Sanctum (Token-based)
- **Content-Type**: `application/json`
- **Accept Header**: `application/json`

---

## 🔐 Authentication Endpoints

### 1. **User Registration**
```http
POST /api/register
Content-Type: application/json
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "user@example.com",
  "password": "Password@123",
  "password_confirmation": "Password@123"
}
```

**Success Response (200):**
```json
{
  "message": "Registration successful!",
  "user": {
    "name": "John Doe",
    "email": "user@example.com",
    "role": null
  },
  "token": "1|abc123def456...",
  "email_sent": true
}
```

**Error Response (422):**
```json
{
  "message": "Validation error",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

---

### 2. **User Login**
```http
POST /api/login
Content-Type: application/json
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "Password@123"
}
```

**Success Response (200):**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified_at": "2025-11-07T10:30:00.000000Z",
    "role": null
  },
  "token": "2|xyz789abc123...",
  "token_type": "Bearer"
}
```

**Error Response (401):**
```json
{
  "message": "Invalid credentials"
}
```

---

### 3. **User Logout**
```http
POST /api/logout
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "message": "Logout successful"
}
```

---

### 4. **Get Current User Profile**
```http
GET /api/user
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "user@example.com",
  "email_verified_at": "2025-11-07T10:30:00.000000Z",
  "role": null,
  "created_at": "2025-11-07T10:00:00.000000Z"
}
```

---

## 📦 Product Endpoints

### 5. **Get All Products**
```http
GET /api/products
```

**Query Parameters:**
- `category_id` (optional): Filter by category
- `search` (optional): Search in product name/description
- `min_price` (optional): Minimum price filter
- `max_price` (optional): Maximum price filter
- `in_stock` (optional): Filter available products (true/false)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 20)

**Example:**
```
GET /api/products?category_id=1&search=solar&min_price=5000&page=1
```

**Success Response (200):**
```json
{
  "products": [
    {
      "id": 1,
      "name": "Solar Panel 100W",
      "description": "High efficiency solar panel",
      "price": "15000.00",
      "category_id": 1,
      "stock_quantity": 50,
      "image_url": "https://web-production-e65f7.up.railway.app/storage/products/image.jpg",
      "is_featured": true,
      "status": "active",
      "category": {
        "id": 1,
        "name": "Solar Panels",
        "slug": "solar-panels"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  }
}
```

---

### 6. **Get Single Product**
```http
GET /api/products/{id}
```

**Success Response (200):**
```json
{
  "product": {
    "id": 1,
    "name": "Solar Panel 100W",
    "description": "High efficiency solar panel with 25-year warranty",
    "price": "15000.00",
    "category_id": 1,
    "stock_quantity": 50,
    "image_url": "https://web-production-e65f7.up.railway.app/storage/products/image.jpg",
    "is_featured": true,
    "status": "active",
    "specifications": {
      "power": "100W",
      "efficiency": "21%",
      "warranty": "25 years"
    },
    "category": {
      "id": 1,
      "name": "Solar Panels",
      "slug": "solar-panels",
      "description": "All types of solar panels"
    },
    "created_at": "2025-11-01T10:00:00.000000Z"
  }
}
```

---

## 🏷️ Category Endpoints

### 7. **Get All Categories**
```http
GET /api/categories
```

**Success Response (200):**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Solar Panels",
      "slug": "solar-panels",
      "description": "High-quality solar panels",
      "image_url": "https://web-production-e65f7.up.railway.app/storage/categories/solar.jpg",
      "products_count": 25
    },
    {
      "id": 2,
      "name": "Inverters",
      "slug": "inverters",
      "description": "Power inverters",
      "image_url": null,
      "products_count": 15
    }
  ]
}
```

---

### 8. **Get Single Category with Products**
```http
GET /api/categories/{id}
```

**Success Response (200):**
```json
{
  "category": {
    "id": 1,
    "name": "Solar Panels",
    "slug": "solar-panels",
    "description": "High-quality solar panels",
    "image_url": "https://web-production-e65f7.up.railway.app/storage/categories/solar.jpg",
    "products": [
      {
        "id": 1,
        "name": "Solar Panel 100W",
        "price": "15000.00",
        "image_url": "https://web-production-e65f7.up.railway.app/storage/products/panel.jpg",
        "stock_quantity": 50
      }
    ]
  }
}
```

---

## 🛒 Order Endpoints

### 9. **Create Order**
```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 5,
      "quantity": 1
    }
  ],
  "customer_address_id": 1,
  "payment_method": "paystack",
  "delivery_method": "home_delivery",
  "notes": "Please call before delivery"
}
```

**Success Response (201):**
```json
{
  "message": "Order created successfully",
  "order": {
    "id": 1,
    "order_number": "ORD-20251107-0001",
    "user_id": 1,
    "total_amount": "45000.00",
    "status": "pending",
    "payment_status": "pending",
    "payment_method": "paystack",
    "delivery_method": "home_delivery",
    "items": [
      {
        "product_id": 1,
        "product_name": "Solar Panel 100W",
        "quantity": 2,
        "price": "15000.00",
        "subtotal": "30000.00"
      }
    ],
    "created_at": "2025-11-07T10:00:00.000000Z"
  },
  "payment_url": "https://checkout.paystack.com/xyz123"
}
```

---

### 10. **Get User Orders**
```http
GET /api/orders
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "orders": [
    {
      "id": 1,
      "order_number": "ORD-20251107-0001",
      "total_amount": "45000.00",
      "status": "processing",
      "payment_status": "paid",
      "payment_method": "paystack",
      "delivery_method": "home_delivery",
      "items_count": 3,
      "created_at": "2025-11-07T10:00:00.000000Z"
    }
  ]
}
```

---

### 11. **Get Single Order**
```http
GET /api/orders/{id}
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-20251107-0001",
    "user_id": 1,
    "total_amount": "45000.00",
    "status": "processing",
    "payment_status": "paid",
    "payment_method": "paystack",
    "payment_reference": "T123456789",
    "delivery_method": "home_delivery",
    "notes": "Please call before delivery",
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Solar Panel 100W",
        "quantity": 2,
        "price": "15000.00",
        "subtotal": "30000.00",
        "product": {
          "image_url": "https://web-production-e65f7.up.railway.app/storage/products/panel.jpg"
        }
      }
    ],
    "address": {
      "full_address": "123 Main Street, Lagos",
      "phone": "+234801234567"
    },
    "created_at": "2025-11-07T10:00:00.000000Z",
    "updated_at": "2025-11-07T11:00:00.000000Z"
  }
}
```

---

## 📍 Customer Address Endpoints

### 12. **Get User Addresses**
```http
GET /api/addresses
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "addresses": [
    {
      "id": 1,
      "address_line1": "123 Main Street",
      "address_line2": "Apartment 4B",
      "city": "Lagos",
      "state": "Lagos",
      "postal_code": "100001",
      "country": "Nigeria",
      "phone": "+234801234567",
      "is_default": true
    }
  ]
}
```

---

### 13. **Create Address**
```http
POST /api/addresses
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "address_line1": "123 Main Street",
  "address_line2": "Apartment 4B",
  "city": "Lagos",
  "state": "Lagos",
  "postal_code": "100001",
  "country": "Nigeria",
  "phone": "+234801234567",
  "is_default": true
}
```

**Success Response (201):**
```json
{
  "message": "Address created successfully",
  "address": {
    "id": 1,
    "address_line1": "123 Main Street",
    "city": "Lagos",
    "state": "Lagos",
    "is_default": true
  }
}
```

---

## 🎯 Pre-Order Endpoints

### 14. **Create Pre-Order**
```http
POST /api/pre-orders
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 5,
  "customer_address_id": 1,
  "notes": "Need by end of month"
}
```

**Success Response (201):**
```json
{
  "message": "Pre-order created successfully",
  "pre_order": {
    "id": 1,
    "pre_order_number": "PRE-20251107-0001",
    "product_id": 1,
    "quantity": 5,
    "total_amount": "75000.00",
    "status": "pending",
    "created_at": "2025-11-07T10:00:00.000000Z"
  }
}
```

---

### 15. **Get User Pre-Orders**
```http
GET /api/pre-orders
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "pre_orders": [
    {
      "id": 1,
      "pre_order_number": "PRE-20251107-0001",
      "product": {
        "id": 1,
        "name": "Solar Panel 100W",
        "image_url": "https://web-production-e65f7.up.railway.app/storage/products/panel.jpg"
      },
      "quantity": 5,
      "total_amount": "75000.00",
      "status": "confirmed",
      "created_at": "2025-11-07T10:00:00.000000Z"
    }
  ]
}
```

---

## 🎁 Promotion Endpoints

### 16. **Get Active Promotions**
```http
GET /api/promotions
```

**Success Response (200):**
```json
{
  "promotions": [
    {
      "id": 1,
      "name": "Black Friday Sale",
      "description": "Get 20% off on all solar panels",
      "discount_type": "percentage",
      "discount_value": "20.00",
      "code": "BLACKFRI20",
      "start_date": "2025-11-01",
      "end_date": "2025-11-30",
      "is_active": true
    }
  ]
}
```

---

### 17. **Apply Promotion Code**
```http
POST /api/promotions/apply
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "code": "BLACKFRI20",
  "cart_total": 50000
}
```

**Success Response (200):**
```json
{
  "valid": true,
  "promotion": {
    "name": "Black Friday Sale",
    "discount_type": "percentage",
    "discount_value": "20.00"
  },
  "discount_amount": 10000,
  "final_total": 40000
}
```

---

## ⚙️ Settings Endpoint

### 18. **Get App Settings**
```http
GET /api/settings
```

**Success Response (200):**
```json
{
  "settings": {
    "site_name": "Solar E-commerce",
    "contact_email": "support@quovatech.com",
    "contact_phone": "+234801234567",
    "currency": "NGN",
    "tax_rate": "7.5",
    "shipping_fee": "2500.00",
    "minimum_order": "10000.00"
  }
}
```

---

## 🔧 Frontend Implementation Guide

### **Step 1: Update API Base URL**

Create an environment configuration file:

**React/Next.js (.env.local):**
```env
NEXT_PUBLIC_API_URL=https://web-production-e65f7.up.railway.app/api
```

**Vue.js (.env.production):**
```env
VUE_APP_API_URL=https://web-production-e65f7.up.railway.app/api
```

**JavaScript/TypeScript Config:**
```javascript
// config/api.js
export const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 
  'https://web-production-e65f7.up.railway.app/api';
```

---

### **Step 2: Setup Axios Instance**

```javascript
// utils/axios.js
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'https://web-production-e65f7.up.railway.app/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add token to requests
apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle errors globally
apiClient.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Token expired, redirect to login
      localStorage.removeItem('auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

---

### **Step 3: API Service Examples**

```javascript
// services/authService.js
import apiClient from '../utils/axios';

export const authService = {
  register: async (userData) => {
    const response = await apiClient.post('/register', userData);
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token);
    }
    return response.data;
  },

  login: async (credentials) => {
    const response = await apiClient.post('/login', credentials);
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token);
    }
    return response.data;
  },

  logout: async () => {
    await apiClient.post('/logout');
    localStorage.removeItem('auth_token');
  },

  getCurrentUser: async () => {
    const response = await apiClient.get('/user');
    return response.data;
  }
};

// services/productService.js
export const productService = {
  getAll: async (filters = {}) => {
    const response = await apiClient.get('/products', { params: filters });
    return response.data;
  },

  getById: async (id) => {
    const response = await apiClient.get(`/products/${id}`);
    return response.data;
  }
};

// services/orderService.js
export const orderService = {
  create: async (orderData) => {
    const response = await apiClient.post('/orders', orderData);
    return response.data;
  },

  getAll: async () => {
    const response = await apiClient.get('/orders');
    return response.data;
  },

  getById: async (id) => {
    const response = await apiClient.get(`/orders/${id}`);
    return response.data;
  }
};
```

---

### **Step 4: Handle File Uploads (Product Images)**

When displaying product images, use the full URL:

```javascript
// components/ProductCard.jsx
const ProductCard = ({ product }) => {
  const imageUrl = product.image_url || 
    'https://via.placeholder.com/300x300?text=No+Image';
  
  return (
    <div className="product-card">
      <img src={imageUrl} alt={product.name} />
      <h3>{product.name}</h3>
      <p>₦{parseFloat(product.price).toLocaleString()}</p>
    </div>
  );
};
```

---

### **Step 5: Handle CORS**

The backend is configured to accept requests from:
- `https://ggtl.com`
- `https://www.ggtl.com`
- `https://web-production-e65f7.up.railway.app`

**If you need to add more domains**, update the `SANCTUM_STATEFUL_DOMAINS` environment variable in Railway.

---

## 🔐 Authentication Flow

### **Registration Flow:**
1. User fills registration form
2. POST to `/api/register`
3. Save token to localStorage
4. Redirect to email verification notice
5. User verifies email (link sent to email)
6. Redirect to dashboard

### **Login Flow:**
1. User fills login form
2. POST to `/api/login`
3. Save token to localStorage
4. Fetch user profile from `/api/user`
5. Redirect to dashboard

### **Protected Route Example:**
```javascript
// React Router protected route
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children }) => {
  const token = localStorage.getItem('auth_token');
  
  if (!token) {
    return <Navigate to="/login" replace />;
  }
  
  return children;
};
```

---

## 💳 Payment Integration (Paystack)

When creating an order, the backend returns a `payment_url`:

```javascript
const handleCheckout = async () => {
  try {
    const response = await orderService.create({
      items: cartItems,
      customer_address_id: selectedAddress,
      payment_method: 'paystack',
      delivery_method: 'home_delivery'
    });
    
    // Redirect to Paystack payment page
    window.location.href = response.order.payment_url;
  } catch (error) {
    console.error('Checkout failed:', error);
  }
};
```

---

## 🐛 Common Issues & Solutions

### **Issue 1: CORS Error**
**Solution:** Make sure your frontend domain is added to `SANCTUM_STATEFUL_DOMAINS` in Railway environment variables.

### **Issue 2: 401 Unauthorized**
**Solution:** Check if token is being sent in Authorization header: `Bearer {token}`

### **Issue 3: Images Not Loading**
**Solution:** Check if image URL starts with `https://web-production-e65f7.up.railway.app/storage/`

### **Issue 4: Token Expired**
**Solution:** Implement token refresh or redirect to login when you get 401 error.

---

## 📊 Status Codes

- **200**: Success
- **201**: Created
- **400**: Bad Request
- **401**: Unauthorized (token invalid/missing)
- **403**: Forbidden (no permission)
- **404**: Not Found
- **422**: Validation Error
- **500**: Server Error

---

## 📞 Support

- **Backend URL**: https://web-production-e65f7.up.railway.app
- **Admin Email**: admin@gifamz.com
- **Support Email**: support@quovatech.com

---

## ✅ Testing Checklist

- [ ] Replace all `localhost:8000` with production URL
- [ ] Test user registration
- [ ] Test user login
- [ ] Test fetching products
- [ ] Test creating orders
- [ ] Test image loading
- [ ] Test authentication token storage
- [ ] Test protected routes
- [ ] Test payment flow
- [ ] Test error handling

---

**Last Updated:** November 7, 2025
**API Version:** 1.0
**Backend Status:** ✅ Production Ready
