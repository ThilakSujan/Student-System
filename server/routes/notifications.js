const express = require('express');
const router = express.Router();
const notificationController = require('../controllers/notificationController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', notificationController.getNotifications);

router.use(requireRole('admin', 'staff'));
router.post('/', notificationController.createNotification);
router.put('/:id', notificationController.updateNotification);
router.delete('/:id', notificationController.deleteNotification);

module.exports = router;
