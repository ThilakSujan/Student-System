const express = require('express');
const router = express.Router();
const userController = require('../controllers/userController');
const { protect } = require('../middlewares/auth');
const { requireAdmin } = require('../middlewares/roleMiddleware');
router.use(protect);
router.use(requireAdmin);

router.get('/', userController.getAllUsers);
router.get('/pending', userController.getPendingUsers);
router.put('/:id/approve', userController.approveUser);
router.put('/:id/reject', userController.rejectUser);
router.delete('/:id', userController.deleteUser);

module.exports = router;
