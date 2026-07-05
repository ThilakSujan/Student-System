const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');
const { protect } = require('../middlewares/auth');

router.post('/login', authController.login);
router.post('/student-login', authController.studentLogin);
router.post('/first-login', authController.firstLoginSetup);
router.post('/register', authController.register);
router.post('/forgot-password', authController.forgotPassword);
router.post('/verify-otp', authController.verifyOTP);
router.post('/reset-password', authController.resetPassword);
router.post('/logout', authController.logout);

router.get('/me', protect, authController.getMe);

module.exports = router;
