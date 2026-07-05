const jwt = require('jsonwebtoken');
const User = require('../models/User');
const Student = require('../models/Student');
const AppError = require('../utils/AppError');
const emailService = require('../services/emailService');

const generateToken = (id, role, email) => {
  return jwt.sign({ id, role, email }, process.env.JWT_SECRET || 'fallback_secret', {
    expiresIn: process.env.JWT_EXPIRES_IN || '1d',
  });
};

exports.login = async (req, res, next) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) throw new AppError('Please provide email and password', 400);

    const user = await User.findOne({ email: email.toLowerCase() }).select('+password');
    if (!user) throw new AppError('Invalid email or password', 401);

    if (user.account_status !== 'Approved') {
      if (user.account_status === 'Pending') throw new AppError('Your account is pending approval by admin', 403);
      if (user.account_status === 'Rejected') throw new AppError('Your account was rejected', 403);
      if (user.account_status === 'Suspended') throw new AppError('Your account is suspended', 403);
    }

    if (!user.password || !(await user.matchPassword(password))) {
      throw new AppError('Invalid email or password', 401);
    }

    const token = generateToken(user._id, user.role, user.email);

    res.status(200).json({
      status: 'success',
      token,
      data: {
        id: user._id,
        email: user.email,
        role: user.role,
        name: user.full_name || user.username
      }
    });
  } catch (error) {
    next(error);
  }
};

exports.studentLogin = async (req, res, next) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) throw new AppError('Please provide email and password', 400);

    const user = await Student.findOne({ email: email.toLowerCase() }).select('+password');
    if (!user) throw new AppError('Invalid email or password', 401);

    if (user.isFirstLogin) {
      return res.status(200).json({
        status: 'success',
        requiresPasswordSetup: true,
        email: user.email,
        studentId: user._id
      });
    }

    if (!user.password || !(await user.matchPassword(password))) {
      throw new AppError('Invalid email or password', 401);
    }

    const token = generateToken(user._id, 'student', user.email);

    res.status(200).json({
      status: 'success',
      token,
      data: {
        id: user._id,
        email: user.email,
        role: 'student',
        name: user.student_name
      }
    });
  } catch (error) {
    next(error);
  }
};

exports.firstLoginSetup = async (req, res, next) => {
  try {
    const { email, password } = req.body;
    if (!email || !password) throw new AppError('Email and new password required', 400);

    const student = await Student.findOne({ email: email.toLowerCase(), isFirstLogin: true });
    if (!student) throw new AppError('Invalid request or password already setup', 400);

    student.password = password;
    student.isFirstLogin = false;
    await student.save();

    const token = generateToken(student._id, 'student', student.email);

    res.status(200).json({
      status: 'success',
      token,
      data: {
        id: student._id,
        email: student.email,
        role: 'student',
        name: student.student_name
      }
    });
  } catch (error) {
    next(error);
  }
};

exports.register = async (req, res, next) => {
  try {
    const { username, email, password, full_name, phone } = req.body;
    
    // Check if user exists
    const existing = await User.findOne({ email: email.toLowerCase() });
    if (existing) throw new AppError('Email already registered', 400);

    const user = await User.create({
      username,
      email: email.toLowerCase(),
      password,
      full_name,
      phone,
      role: 'staff',
      account_status: 'Pending'
    });

    // Fire email in background
    emailService.sendRegistrationEmail(user.email, user.full_name || user.username).catch(console.error);

    res.status(201).json({
      status: 'success',
      message: 'Registration successful. Please wait for admin approval.'
    });
  } catch (error) {
    next(error);
  }
};

exports.forgotPassword = async (req, res, next) => {
  try {
    const { email } = req.body;
    const user = await User.findOne({ email: email.toLowerCase() });
    if (!user) throw new AppError('No user found with that email address', 404);

    const otp = await user.generateOTP();
    await user.save({ validateBeforeSave: false });

    // Send email
    await emailService.sendOtpEmail(user.email, user.full_name || user.username, otp);

    res.status(200).json({
      status: 'success',
      message: 'OTP sent to email'
    });
  } catch (error) {
    next(error);
  }
};

exports.verifyOTP = async (req, res, next) => {
  try {
    const { email, otp } = req.body;
    const user = await User.findOne({ email: email.toLowerCase() }).select('+reset_otp +otp_expiry');
    if (!user) throw new AppError('User not found', 404);

    const result = await user.verifyOTP(otp);
    await user.save({ validateBeforeSave: false });

    if (!result.success) {
      throw new AppError(result.message, 400);
    }

    res.status(200).json({ status: 'success', message: 'OTP verified successfully' });
  } catch (error) {
    next(error);
  }
};

exports.resetPassword = async (req, res, next) => {
  try {
    const { email, newPassword } = req.body;
    const user = await User.findOne({ email: email.toLowerCase() });
    if (!user) throw new AppError('User not found', 404);

    if (!user.otp_verified) throw new AppError('Please verify OTP first', 400);

    user.password = newPassword;
    user.otp_verified = false;
    await user.save();

    res.status(200).json({ status: 'success', message: 'Password reset successfully' });
  } catch (error) {
    next(error);
  }
};

exports.getMe = async (req, res, next) => {
  try {
    const role = req.userRole;
    let userData = null;
    if (role === 'student') {
      const student = await Student.findById(req.user.id);
      if (student) userData = { id: student._id, email: student.email, name: student.student_name, role };
    } else {
      const user = await User.findById(req.user.id);
      if (user) userData = { id: user._id, email: user.email, name: user.full_name || user.username, role };
    }

    if (!userData) throw new AppError('User not found', 404);

    res.status(200).json({ status: 'success', data: userData });
  } catch (error) {
    next(error);
  }
};

exports.logout = (req, res) => {
  res.status(200).json({ status: 'success' });
};
