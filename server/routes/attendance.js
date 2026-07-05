const express = require('express');
const router = express.Router();
const attendanceController = require('../controllers/attendanceController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', attendanceController.getAttendance);
router.post('/mark', requireRole('admin', 'staff'), attendanceController.markAttendance);

module.exports = router;
