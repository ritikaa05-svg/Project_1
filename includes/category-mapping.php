<?php

class CategoryMapping {
    
    // Employee categories from database
    const EMPLOYEE_CATEGORIES = [
        'Unskilled Labor',
        'Skilled Labor', 
        'Technical',
        'Professional'
    ];
    
    // Job categories that customers can post
    const JOB_CATEGORIES = [
        'Plumbing',
        'Electrical',
        'Carpentry',
        'Cleaning',
        'Gardening',
        'Painting',
        'Moving',
        'Repair',
        'Installation',
        'Cooking',
        'Driving',
        'Security',
        'IT Support',
        'Web Development',
        'Graphic Design',
        'Consulting',
        'Legal Services',
        'Medical Services',
        'Teaching',
        'Other'
    ];
    
    // Mapping from job categories to employee categories
    const CATEGORY_MAPPING = [
        // Unskilled Labor jobs
        'Cleaning' => 'Unskilled Labor',
        'Moving' => 'Unskilled Labor',
        'Gardening' => 'Unskilled Labor',
        'Driving' => 'Unskilled Labor',
        'Security' => 'Unskilled Labor',
        
        // Skilled Labor jobs
        'Plumbing' => 'Skilled Labor',
        'Electrical' => 'Skilled Labor',
        'Carpentry' => 'Skilled Labor',
        'Painting' => 'Skilled Labor',
        'Repair' => 'Skilled Labor',
        'Installation' => 'Skilled Labor',
        'Cooking' => 'Skilled Labor',
        
        // Technical jobs
        'IT Support' => 'Technical',
        'Web Development' => 'Technical',
        'Graphic Design' => 'Technical',
        
        // Professional jobs
        'Consulting' => 'Professional',
        'Legal Services' => 'Professional',
        'Medical Services' => 'Professional',
        'Teaching' => 'Professional',
        
        // Default for unknown categories
        'Other' => 'Skilled Labor'
    ];
    
  
    public static function getEmployeeCategory($jobCategory) {
        return self::CATEGORY_MAPPING[$jobCategory] ?? 'Skilled Labor';
    }
    
    
    public static function getJobCategoriesForEmployee($employeeCategory) {
        $matchingJobs = [];
        foreach (self::CATEGORY_MAPPING as $jobCategory => $empCategory) {
            if ($empCategory === $employeeCategory) {
                $matchingJobs[] = $jobCategory;
            }
        }
        return $matchingJobs;
    }
    
    
    public static function getJobCategories() {
        return self::JOB_CATEGORIES;
    }
    
   
    public static function getEmployeeCategories() {
        return self::EMPLOYEE_CATEGORIES;
    }
    
    
    public static function isValidJobCategory($category) {
        return in_array($category, self::JOB_CATEGORIES);
    }
    
  
    
    public static function isValidEmployeeCategory($category) {
        return in_array($category, self::EMPLOYEE_CATEGORIES);
    }
    
   
    public static function getCategoryDescription($category) {
        $descriptions = [
            'Unskilled Labor' => 'General workers, helpers, and manual laborers for basic tasks',
            'Skilled Labor' => 'Plumbers, electricians, masons, carpenters, and other skilled tradespeople',
            'Technical' => 'Software engineers, IT professionals, technicians, and technical specialists',
            'Professional' => 'Doctors, lawyers, consultants, and other highly qualified professionals'
        ];
        
        return $descriptions[$category] ?? 'General category';
    }
}
?> 