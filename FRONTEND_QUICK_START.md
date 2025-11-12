# 🎯 Quick Instructions for Frontend AI - Image Upload Changes

## What Changed

The backend **NO LONGER accepts image file uploads**. Instead, it now accepts **image URLs** from cloud storage services.

---

## Your Task

### 1. **Upload Images to Cloud Storage First**
   - Use Imgbb, Cloudinary, or Uploadcare
   - Get API key from your chosen service
   - Upload user-selected images to cloud service
   - Get back permanent URLs

### 2. **Send URLs to Backend**
   - Send JSON (not FormData) with image URLs
   - Use `Content-Type: application/json`

---

## Quick Example

```javascript
// 1. Upload to Imgbb
const uploadToCloud = async (file) => {
  const formData = new FormData();
  formData.append('image', file);
  formData.append('key', 'YOUR_IMGBB_API_KEY'); // Get from https://api.imgbb.com/
  
  const response = await fetch('https://api.imgbb.com/1/upload', {
    method: 'POST',
    body: formData
  });
  
  const data = await response.json();
  return data.data.url; // Returns permanent URL
};

// 2. Send to backend
const createProduct = async (productData, imageFiles) => {
  // Upload images first
  const imageUrls = await Promise.all(
    imageFiles.map(file => uploadToCloud(file))
  );
  
  // Send to backend
  const response = await fetch('https://web-production-d1120.up.railway.app/api/admin/products', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      ...productData,
      images: imageUrls // ← Array of URLs, not files!
    })
  });
  
  return response.json();
};
```

---

## Request Format

### Product/PreOrder Creation:
```json
{
  "name": "Product Name",
  "category_id": 1,
  "price": 45000,
  "stock": 10,
  "description": "Product description",
  "images": [
    "https://i.ibb.co/xyz123/image1.jpg",
    "https://i.ibb.co/abc456/image2.jpg"
  ]
}
```

---

## Display Images

```javascript
// Images are already full URLs in the response
<img src={product.images[0]} alt={product.name} />
```

---

## Important Notes

- ✅ Use `Content-Type: application/json` (NOT `multipart/form-data`)
- ✅ Send image URLs as array of strings
- ✅ Maximum 10 images per product/pre-order
- ✅ Images persist permanently (no more loss on deployment)

---

## Get Imgbb API Key (Free)
1. Go to https://api.imgbb.com/
2. Sign up for free account
3. Get your API key
4. Unlimited uploads on free tier

---

**Full documentation:** See `FRONTEND_IMAGE_UPLOAD_INSTRUCTIONS.md` for detailed examples with React, Vue, and vanilla JavaScript.

**Endpoints affected:**
- `POST /api/admin/products`
- `PUT /api/admin/products/{id}`
- `POST /api/admin/pre-orders`
- `PUT /api/admin/pre-orders/{id}`

**Backend is deployed and ready!** 🚀
