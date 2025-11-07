# Quick Frontend Setup - Copy & Paste Guide

## 🚀 REPLACE THIS EVERYWHERE IN YOUR FRONTEND:

### **OLD (Local Development):**
```
http://localhost:8000/api
```

### **NEW (Production):**
```
https://web-production-e65f7.up.railway.app/api
```

---

## 📝 Environment Variables to Update

### React/Next.js (.env.local or .env.production):
```env
NEXT_PUBLIC_API_URL=https://web-production-e65f7.up.railway.app/api
NEXT_PUBLIC_API_BASE_URL=https://web-production-e65f7.up.railway.app
```

### Vue.js (.env.production):
```env
VUE_APP_API_URL=https://web-production-e65f7.up.railway.app/api
VUE_APP_BASE_URL=https://web-production-e65f7.up.railway.app
```

### Angular (environment.prod.ts):
```typescript
export const environment = {
  production: true,
  apiUrl: 'https://web-production-e65f7.up.railway.app/api'
};
```

---

## 🔧 Ready-to-Use Axios Configuration

### Copy this entire file to your project:

**File: `src/config/api.js` or `src/utils/axios.js`**

```javascript
import axios from 'axios';

// API Base URL - Change this for production
const API_BASE_URL = 'https://web-production-e65f7.up.railway.app/api';

// Create axios instance
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 30000 // 30 seconds
});

// Request interceptor - Add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Unauthorized - Clear token and redirect to login
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    
    if (error.response?.status === 422) {
      // Validation error - return validation messages
      console.error('Validation Error:', error.response.data.errors);
    }
    
    return Promise.reject(error);
  }
);

export default apiClient;
export { API_BASE_URL };
```

---

## 🎯 Common API Calls - Copy & Paste

### Authentication:

```javascript
import apiClient from './config/api';

// REGISTER
const register = async (userData) => {
  const response = await apiClient.post('/register', {
    first_name: userData.firstName,
    last_name: userData.lastName,
    email: userData.email,
    password: userData.password,
    password_confirmation: userData.passwordConfirmation
  });
  localStorage.setItem('auth_token', response.data.token);
  return response.data;
};

// LOGIN
const login = async (email, password) => {
  const response = await apiClient.post('/login', { email, password });
  localStorage.setItem('auth_token', response.data.token);
  localStorage.setItem('user', JSON.stringify(response.data.user));
  return response.data;
};

// LOGOUT
const logout = async () => {
  await apiClient.post('/logout');
  localStorage.removeItem('auth_token');
  localStorage.removeItem('user');
};

// GET CURRENT USER
const getCurrentUser = async () => {
  const response = await apiClient.get('/user');
  return response.data;
};
```

### Products:

```javascript
// GET ALL PRODUCTS
const getProducts = async (filters = {}) => {
  const response = await apiClient.get('/products', { 
    params: filters // { category_id: 1, search: 'solar', page: 1 }
  });
  return response.data;
};

// GET SINGLE PRODUCT
const getProduct = async (productId) => {
  const response = await apiClient.get(`/products/${productId}`);
  return response.data.product;
};

// SEARCH PRODUCTS
const searchProducts = async (searchTerm) => {
  const response = await apiClient.get('/products', {
    params: { search: searchTerm }
  });
  return response.data.products;
};
```

### Categories:

```javascript
// GET ALL CATEGORIES
const getCategories = async () => {
  const response = await apiClient.get('/categories');
  return response.data.categories;
};

// GET CATEGORY WITH PRODUCTS
const getCategoryProducts = async (categoryId) => {
  const response = await apiClient.get(`/categories/${categoryId}`);
  return response.data.category;
};
```

### Orders:

```javascript
// CREATE ORDER
const createOrder = async (orderData) => {
  const response = await apiClient.post('/orders', {
    items: orderData.items, // [{ product_id: 1, quantity: 2 }]
    customer_address_id: orderData.addressId,
    payment_method: 'paystack',
    delivery_method: 'home_delivery',
    notes: orderData.notes
  });
  return response.data;
};

// GET USER ORDERS
const getUserOrders = async () => {
  const response = await apiClient.get('/orders');
  return response.data.orders;
};

// GET SINGLE ORDER
const getOrder = async (orderId) => {
  const response = await apiClient.get(`/orders/${orderId}`);
  return response.data.order;
};
```

### Addresses:

```javascript
// GET USER ADDRESSES
const getAddresses = async () => {
  const response = await apiClient.get('/addresses');
  return response.data.addresses;
};

// CREATE ADDRESS
const createAddress = async (addressData) => {
  const response = await apiClient.post('/addresses', {
    address_line1: addressData.street,
    address_line2: addressData.apartment,
    city: addressData.city,
    state: addressData.state,
    postal_code: addressData.postalCode,
    country: 'Nigeria',
    phone: addressData.phone,
    is_default: addressData.isDefault
  });
  return response.data.address;
};
```

---

## 🖼️ Image URL Handling

### Display Product/Category Images:

```javascript
// React Component Example
const ProductImage = ({ product }) => {
  const imageUrl = product.image_url || '/placeholder.png';
  
  return (
    <img 
      src={imageUrl} 
      alt={product.name}
      onError={(e) => {
        e.target.src = '/placeholder.png'; // Fallback image
      }}
    />
  );
};
```

### Full Image URL Format:
```
https://web-production-e65f7.up.railway.app/storage/products/filename.jpg
https://web-production-e65f7.up.railway.app/storage/categories/filename.jpg
```

---

## 🔐 Protected Route Component

### React Router:

```javascript
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children }) => {
  const token = localStorage.getItem('auth_token');
  
  if (!token) {
    return <Navigate to="/login" replace />;
  }
  
  return children;
};

// Usage:
<Route path="/dashboard" element={
  <ProtectedRoute>
    <Dashboard />
  </ProtectedRoute>
} />
```

### Next.js Middleware:

```javascript
// middleware.js
import { NextResponse } from 'next/server';

export function middleware(request) {
  const token = request.cookies.get('auth_token');
  
  if (!token && request.nextUrl.pathname.startsWith('/dashboard')) {
    return NextResponse.redirect(new URL('/login', request.url));
  }
  
  return NextResponse.next();
}
```

---

## 💳 Payment Flow (Paystack)

```javascript
const handleCheckout = async (cartItems, addressId) => {
  try {
    const orderData = {
      items: cartItems.map(item => ({
        product_id: item.id,
        quantity: item.quantity
      })),
      customer_address_id: addressId,
      payment_method: 'paystack',
      delivery_method: 'home_delivery'
    };
    
    const response = await apiClient.post('/orders', orderData);
    
    // Backend returns Paystack payment URL
    if (response.data.payment_url) {
      // Redirect user to Paystack
      window.location.href = response.data.payment_url;
    }
  } catch (error) {
    console.error('Checkout failed:', error.response?.data);
  }
};
```

---

## 🎨 Format Currency (Nigerian Naira)

```javascript
const formatPrice = (price) => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN'
  }).format(price);
};

// Usage:
formatPrice(15000); // ₦15,000.00
```

---

## 📱 Complete React Example

```javascript
// pages/Products.jsx
import { useState, useEffect } from 'react';
import apiClient from '../config/api';

const Products = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      const response = await apiClient.get('/products');
      setProducts(response.data.products);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load products');
      console.error('Error fetching products:', err);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="products-grid">
      {products.map(product => (
        <div key={product.id} className="product-card">
          <img 
            src={product.image_url || '/placeholder.png'} 
            alt={product.name} 
          />
          <h3>{product.name}</h3>
          <p>{product.description}</p>
          <p className="price">
            ₦{parseFloat(product.price).toLocaleString()}
          </p>
          <button>Add to Cart</button>
        </div>
      ))}
    </div>
  );
};

export default Products;
```

---

## ✅ Testing Your Integration

### 1. Test API Connection:
Open browser console and run:
```javascript
fetch('https://web-production-e65f7.up.railway.app/api/categories')
  .then(r => r.json())
  .then(d => console.log('API Response:', d))
  .catch(e => console.error('API Error:', e));
```

### 2. Test Authentication:
```javascript
fetch('https://web-production-e65f7.up.railway.app/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'test@example.com',
    password: 'password123'
  })
})
.then(r => r.json())
.then(d => console.log('Login Response:', d));
```

---

## 🚨 Common Errors & Fixes

### Error: "Network Error" or "CORS Policy"
**Fix:** Make sure your domain is added to Railway environment variables:
```
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

### Error: "401 Unauthorized"
**Fix:** Check if token is being sent:
```javascript
console.log('Token:', localStorage.getItem('auth_token'));
```

### Error: "422 Validation Error"
**Fix:** Check request body format matches API documentation

### Error: Images not loading
**Fix:** Check if image URL starts with `https://web-production-e65f7.up.railway.app/storage/`

---

## 📞 Quick Reference URLs

| Purpose | URL |
|---------|-----|
| API Base | `https://web-production-e65f7.up.railway.app/api` |
| Products | `https://web-production-e65f7.up.railway.app/api/products` |
| Categories | `https://web-production-e65f7.up.railway.app/api/categories` |
| Login | `https://web-production-e65f7.up.railway.app/api/login` |
| Register | `https://web-production-e65f7.up.railway.app/api/register` |
| Orders | `https://web-production-e65f7.up.railway.app/api/orders` |

---

**Need Help?** Check the full documentation: `FRONTEND_INTEGRATION_GUIDE.md`

**Last Updated:** November 7, 2025
