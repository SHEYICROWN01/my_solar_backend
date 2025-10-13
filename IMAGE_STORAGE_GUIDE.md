# 📸 Image Storage Guide for Frontend Integration

## 🎯 Overview

This guide explains how product images are stored in your Laravel API and how to access them from your frontend.

---

## 📁 Storage Architecture

### **Backend Storage:**
```
storage/app/public/products/
├── hash1234.jpg
├── hash5678.png
└── hash9012.jpg
```

### **Public Access (via symlink):**
```
public/storage/products/
└── [symbolic link to storage/app/public/products]
```

---

## ⚙️ STEP 1: Setup Storage Link (REQUIRED!)

### **Run this command ONCE:**

```bash
php artisan storage:link
```

### **What this does:**
Creates a symbolic link: `public/storage` → `storage/app/public`

### **Verify it worked:**
```bash
# Check if symlink exists
ls -la public/storage

# You should see something like:
# storage -> ../storage/app/public
```

### **⚠️ Important Notes:**
- Run this on **every environment** (local, staging, production)
- If you ever delete `public/storage`, run the command again
- On production servers, ensure you have symlink permissions

---

## 📡 API Response Format

### **BEFORE (old format):**
```json
{
  "product": {
    "id": 1,
    "name": "Solar Panel 500W",
    "images": [
      "products/abc123.jpg",
      "products/def456.jpg"
    ]
  }
}
```

### **AFTER (new format with full URLs):**
```json
{
  "product": {
    "id": 1,
    "name": "Solar Panel 500W",
    "images": [
      "products/abc123.jpg",
      "products/def456.jpg"
    ],
    "image_urls": [
      "http://localhost:8000/storage/products/abc123.jpg",
      "http://localhost:8000/storage/products/def456.jpg"
    ]
  }
}
```

**✅ The `image_urls` field is automatically added to all product responses!**

---

## 💻 Frontend Usage Examples

### **React/Next.js Example:**

```jsx
// Fetch product
const response = await fetch('http://localhost:8000/api/products/1');
const { product } = await response.json();

// Display images using the full URLs
return (
  <div>
    <h2>{product.name}</h2>
    <div className="image-gallery">
      {product.image_urls.map((url, index) => (
        <img 
          key={index}
          src={url} 
          alt={`${product.name} - Image ${index + 1}`}
          className="product-image"
        />
      ))}
    </div>
  </div>
);
```

### **Vue.js Example:**

```vue
<template>
  <div>
    <h2>{{ product.name }}</h2>
    <div class="image-gallery">
      <img 
        v-for="(url, index) in product.image_urls"
        :key="index"
        :src="url"
        :alt="`${product.name} - Image ${index + 1}`"
        class="product-image"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const product = ref(null);

onMounted(async () => {
  const response = await fetch('http://localhost:8000/api/products/1');
  const data = await response.json();
  product.value = data.product;
});
</script>
```

### **Vanilla JavaScript Example:**

```javascript
fetch('http://localhost:8000/api/products/1')
  .then(response => response.json())
  .then(({ product }) => {
    const gallery = document.getElementById('gallery');
    
    product.image_urls.forEach((url, index) => {
      const img = document.createElement('img');
      img.src = url;
      img.alt = `${product.name} - Image ${index + 1}`;
      img.className = 'product-image';
      gallery.appendChild(img);
    });
  });
```

---

## 🔍 Image URL Structure

### **Development:**
```
http://localhost:8000/storage/products/xyz123.jpg
```

### **Production:**
```
https://yourdomain.com/storage/products/xyz123.jpg
```

**The URL automatically adjusts based on your `APP_URL` in `.env`**

---

## 📤 Uploading Images from Frontend

### **FormData Example (React/JavaScript):**

```javascript
const uploadProduct = async (formData) => {
  // Create FormData
  const data = new FormData();
  data.append('name', 'Solar Panel 500W');
  data.append('category_id', 1);
  data.append('price', 45000);
  data.append('stock', 10);
  data.append('description', 'High-efficiency solar panel');
  data.append('power', '500W');
  data.append('warranty', '25 years');
  
  // Add specifications as JSON
  data.append('specifications', JSON.stringify({
    efficiency: '21%',
    dimensions: '1956×992×40mm',
    weight: '27.5kg'
  }));
  
  // Add multiple images
  const imageFiles = document.getElementById('images').files;
  for (let i = 0; i < imageFiles.length; i++) {
    data.append('images[]', imageFiles[i]);
  }
  
  // Send to API
  const response = await fetch('http://localhost:8000/api/products', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      // Don't set Content-Type - browser sets it automatically with boundary
    },
    body: data
  });
  
  const result = await response.json();
  console.log('Product created:', result.product);
  console.log('Image URLs:', result.product.image_urls);
};
```

### **HTML Form Example:**

```html
<form id="productForm" enctype="multipart/form-data">
  <input type="text" name="name" placeholder="Product Name" required>
  <input type="number" name="category_id" placeholder="Category ID" required>
  <input type="number" name="price" placeholder="Price" required>
  <input type="number" name="stock" placeholder="Stock" required>
  <textarea name="description" placeholder="Description" required></textarea>
  
  <!-- Image upload - accepts multiple files -->
  <input 
    type="file" 
    id="images" 
    name="images[]" 
    multiple 
    accept="image/jpeg,image/png,image/jpg"
  >
  <small>Max 5MB per image, JPEG/PNG only</small>
  
  <button type="submit">Create Product</button>
</form>

<script>
document.getElementById('productForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  
  const response = await fetch('http://localhost:8000/api/products', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${yourToken}`,
    },
    body: formData
  });
  
  const { product } = await response.json();
  alert('Product created! Images: ' + product.image_urls.join(', '));
});
</script>
```

---

## 🛠️ Validation Rules

### **Backend Validation (already implemented):**
- File type: `jpeg`, `png`, `jpg` only
- Max size: **5MB per image**
- Multiple images allowed
- All images validated individually

### **Frontend Validation (recommended):**

```javascript
const validateImages = (files) => {
  const maxSize = 5 * 1024 * 1024; // 5MB
  const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
  
  for (let file of files) {
    // Check file type
    if (!allowedTypes.includes(file.type)) {
      alert(`${file.name} is not a valid image type. Use JPEG or PNG.`);
      return false;
    }
    
    // Check file size
    if (file.size > maxSize) {
      alert(`${file.name} is too large. Max size is 5MB.`);
      return false;
    }
  }
  
  return true;
};

// Usage
const imageInput = document.getElementById('images');
imageInput.addEventListener('change', (e) => {
  if (!validateImages(e.target.files)) {
    e.target.value = ''; // Clear invalid files
  }
});
```

---

## 🎨 Image Optimization Tips

### **1. Display Thumbnails:**

```jsx
// In your frontend, create thumbnails for grid views
<img 
  src={product.image_urls[0]} 
  alt={product.name}
  className="w-full h-64 object-cover rounded-lg"
  loading="lazy"
/>
```

### **2. Lazy Loading:**

```jsx
<img 
  src={url} 
  alt={product.name}
  loading="lazy" // Native lazy loading
/>
```

### **3. Image Placeholder:**

```jsx
const [imageLoaded, setImageLoaded] = useState(false);

<div className="relative">
  {!imageLoaded && (
    <div className="absolute inset-0 bg-gray-200 animate-pulse" />
  )}
  <img 
    src={product.image_urls[0]} 
    alt={product.name}
    onLoad={() => setImageLoaded(true)}
    className={imageLoaded ? 'opacity-100' : 'opacity-0'}
  />
</div>
```

---

## 🐛 Troubleshooting

### **Problem: Images not showing (404 error)**

**Solution:**
```bash
# 1. Check if symlink exists
ls -la public/storage

# 2. If missing, create it
php artisan storage:link

# 3. Check file permissions (Unix/Linux)
chmod -R 755 storage/app/public
```

### **Problem: "The storage link already exists"**

**Solution:**
```bash
# Remove existing link and recreate
rm public/storage
php artisan storage:link
```

### **Problem: Images work locally but not in production**

**Solution:**
```bash
# On production server:
php artisan storage:link

# Check server permissions
chmod -R 755 storage
chmod -R 755 public/storage
```

### **Problem: Getting relative paths instead of full URLs**

**Solution:**
```javascript
// If you only have the path, construct the URL:
const getFullImageUrl = (path) => {
  const apiBaseUrl = 'http://localhost:8000'; // or your API URL
  return `${apiBaseUrl}/storage/${path}`;
};

// But with the updated model, you should use image_urls directly!
```

---

## 🔐 Security Notes

### **1. Validate file uploads:**
✅ Already implemented in `ProductController`

### **2. Prevent directory traversal:**
✅ Laravel's `Storage` facade handles this automatically

### **3. Limit file sizes:**
✅ Currently limited to 5MB per image

### **4. Restrict file types:**
✅ Only JPEG and PNG allowed

---

## 🚀 Production Deployment Checklist

- [ ] Run `php artisan storage:link` on production server
- [ ] Set correct `APP_URL` in production `.env`
- [ ] Configure CDN (optional, for better performance)
- [ ] Set up image optimization pipeline (optional)
- [ ] Configure proper file permissions (755 for directories)
- [ ] Enable CORS for your production frontend domain
- [ ] Consider using cloud storage (AWS S3, DigitalOcean Spaces, etc.)

---

## 📊 Testing Image Storage

### **Test Image Upload:**

```bash
# Using cURL
curl -X POST http://localhost:8000/api/products \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name=Test Solar Panel" \
  -F "category_id=1" \
  -F "price=50000" \
  -F "stock=5" \
  -F "description=Test product" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.jpg"
```

### **Test Image Access:**

```bash
# Get product
curl http://localhost:8000/api/products/1

# You should see image_urls array with full URLs
# Try accessing one URL in your browser
```

---

## 🎯 Quick Reference

| What | Value |
|------|-------|
| Storage Disk | `public` |
| Storage Path | `storage/app/public/products/` |
| Public URL | `/storage/products/{filename}` |
| Full URL | `http://localhost:8000/storage/products/{filename}` |
| Max File Size | 5MB per image |
| Allowed Types | JPEG, PNG, JPG |
| API Field (paths) | `images` (array) |
| API Field (URLs) | `image_urls` (array) - **USE THIS!** |

---

## ✅ Summary

1. **Run:** `php artisan storage:link` ✅
2. **Use:** `product.image_urls` in your frontend ✅
3. **Upload:** Send images as `FormData` with key `images[]` ✅
4. **Display:** Use the full URLs directly in `<img>` tags ✅

**Your images are now ready for frontend integration! 🎉**
