const mongoose = require('mongoose');
const app = require('../app'); // Import the Express app

let cachedDb = null;

async function connectToDatabase() {
  if (cachedDb) {
    return cachedDb;
  }

  // Connect to MongoDB Atlas (Make sure MONGO_URI is set in Vercel Dashboard)
  const db = await mongoose.connect(process.env.MONGO_URI, {
    useNewUrlParser: true,
    useUnifiedTopology: true,
    serverSelectionTimeoutMS: 5000,
  });

  cachedDb = db;
  console.log('✅ MongoDB Connected (Serverless)');
  return db;
}

// Vercel serverless function entrypoint
module.exports = async (req, res) => {
  try {
    // Ensure DB is connected before handling the request
    await connectToDatabase();
  } catch (error) {
    console.error('Database connection failed:', error);
    return res.status(500).json({ success: false, message: 'Database connection failed' });
  }

  // Pass the request to the Express app
  return app(req, res);
};
