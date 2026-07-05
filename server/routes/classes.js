const express = require('express');
const router = express.Router();
const classController = require('../controllers/classController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', classController.getAllClasses);
router.get('/:id', classController.getClass);

// Only admin/staff can modify classes
router.use(requireRole('admin', 'staff'));

router.post('/', classController.createClass);
router.put('/:id', classController.updateClass);
router.delete('/:id', classController.deleteClass);
router.post('/:id/students', classController.assignStudents);
router.delete('/:id/students/:studentId', classController.removeStudent);

module.exports = router;
