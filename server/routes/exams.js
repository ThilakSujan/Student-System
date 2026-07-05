const express = require('express');
const router = express.Router();
const examController = require('../controllers/examController');
const { protect } = require('../middlewares/auth');
const { requireRole } = require('../middlewares/roleMiddleware');

router.use(protect);

router.get('/', examController.getExams);

router.use(requireRole('admin', 'staff'));
router.post('/', examController.createExam);
router.put('/:id', examController.updateExam);
router.delete('/:id', examController.deleteExam);

module.exports = router;
