require('dotenv').config();
const app = require('./app');
const connectDB = require('./config/db');

/**
 * server.js — Application entry point
 * Connects to MongoDB then starts the HTTP server
 */
const PORT = process.env.PORT || 5000;

const startServer = async () => {
  try {
    await connectDB();

    const server = app.listen(PORT, () => {
      console.log('\n🚀 ══════════════════════════════════════════════');
      console.log(`   Student Management System — MERN Backend`);
      console.log(`   Server running on http://localhost:${PORT}`);
      console.log(`   Environment: ${process.env.NODE_ENV}`);
      console.log(`   API Base: http://localhost:${PORT}/api`);
      console.log('🚀 ══════════════════════════════════════════════\n');
    });

    // Graceful shutdown
    const shutdown = (signal) => {
      console.log(`\n⚡ ${signal} received. Shutting down gracefully...`);
      server.close(() => {
        console.log('✅ HTTP server closed');
        process.exit(0);
      });
    };

    process.on('SIGTERM', () => shutdown('SIGTERM'));
    process.on('SIGINT', () => shutdown('SIGINT'));

    // Unhandled promise rejections
    process.on('unhandledRejection', (err) => {
      console.error('❌ Unhandled Rejection:', err.message);
      server.close(() => process.exit(1));
    });

  } catch (error) {
    console.error('❌ Failed to start server:', error.message);
  }
};

startServer();
