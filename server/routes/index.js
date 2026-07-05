const express = require('express');
const router = express.Router();

const authRoutes = require('./auth');
const dashboardRoutes = require('./dashboard');
const userRoutes = require('./users');
const studentRoutes = require('./students');
const classRoutes = require('./classes');
const subjectRoutes = require('./subjects');
const markRoutes = require('./marks');
const attendanceRoutes = require('./attendance');
const feeRoutes = require('./fees');
const examRoutes = require('./exams');
const notificationRoutes = require('./notifications');
const instituteRoutes = require('./institute');

router.use('/auth', authRoutes);
router.use('/dashboard', dashboardRoutes);
router.use('/users', userRoutes);
router.use('/students', studentRoutes);
router.use('/classes', classRoutes);
router.use('/subjects', subjectRoutes);
router.use('/marks', markRoutes);
router.use('/attendance', attendanceRoutes);
router.use('/fees', feeRoutes);
router.use('/exams', examRoutes);
router.use('/notifications', notificationRoutes);
router.use('/institute', instituteRoutes);

module.exports = router;
