const express = require('express');
const router = express.Router();
const emailController = require('../controllers/emailController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);
router.use(requireRole('admin'));

router.get('/', emailController.getEmailLogs);
router.delete('/:id', emailController.deleteEmailLog);

module.exports = router;
