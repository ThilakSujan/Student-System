import api from './axios';

export const getInstitute = () => api.get('/institute');
export const updateInstitute = (data) => api.put('/institute', data);
