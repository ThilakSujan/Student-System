const express = require('express');
const router = express.Router();
const studentController = require('../controllers/studentController');
const { protect } = require('../middlewares/auth');
const { requireRole, requireAdmin } = require('../middlewares/roleMiddleware');
const multer = require('multer');

// Configure multer for CSV upload
const upload = multer({ dest: 'uploads/csv/' });

router.use(protect);
// Wait, students can't access all these. Let's apply role-based access per route.

router.get('/', requireRole('admin', 'staff'), studentController.getAllStudents);
router.post('/', requireRole('admin', 'staff'), studentController.createStudent);
router.post('/import-csv', requireRole('admin', 'staff'), upload.single('file'), studentController.importStudents);

router.get('/:id', studentController.getStudent); // Accessible by student (if self) or admin/staff
router.put('/:id', requireRole('admin', 'staff'), studentController.updateStudent);
router.delete('/:id', requireRole('admin', 'staff'), studentController.deleteStudent);

module.exports = router;
